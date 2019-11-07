<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface;
use GuzzleHttp\Psr7\Response;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleCacheErrorEvent;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Trait CacheTrait
 * @package M6Web\Bundle\GuzzleHttpBundle\Handler
 */
trait CacheTrait
{
    /** @var CacheInterface */
    protected $cache;

    /** @var bool Do we have to cache 4* responses */
    protected $cacheClientErrors;

    /** @var bool Do we have to cache 5* responses */
    protected $cacheServerErrors;

    /** @var bool  */
    protected $debug = false;

    /** @var int */
    protected $defaultTtl;

    /** @var bool  */
    protected $isIgnoreCacheErrors = false;

    /** @var array  */
    protected static $methods = ['GET', 'HEAD', 'OPTIONS'];

    /** @var bool */
    protected $useHeaderTtl;

    /**
     * @param CacheInterface $cache
     * @param int            $defaultTtl
     * @param bool           $useHeaderTtl
     * @param bool           $cacheServerErrors
     * @param bool           $cacheClientErrors
     * @param bool           $isIgnoreCacheErrors
     */
    public function setCache(CacheInterface $cache, int $defaultTtl, bool $useHeaderTtl, bool $cacheServerErrors = true, bool $cacheClientErrors = true, bool $isIgnoreCacheErrors = false)
    {
        $this->cache = $cache;
        $this->cacheClientErrors = $cacheClientErrors;
        $this->cacheServerErrors = $cacheServerErrors;
        $this->defaultTtl = $defaultTtl;
        $this->isIgnoreCacheErrors = $isIgnoreCacheErrors;
        $this->useHeaderTtl = $useHeaderTtl;
    }

    /**
     * Set the debug mode
     *
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Get cache key
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected static function getKey(RequestInterface $request)
    {
        // Generate headerline for the cache key. X- headers are ignored except those included in the Vary
        $vary = array_flip($request->getHeader('Vary'));
        $headerLine = implode('', array_map(
            [$request, 'getHeaderLine'],
            array_filter(
                array_keys($request->getHeaders()),
                function ($header) use ($vary) {
                    return (0 !== stripos($header, 'x-')
                        || array_key_exists($header, $vary)
                    );
                }
            )
        ));

        return $request->getMethod().'-'.$request->getUri().'-'.md5($headerLine);
    }

    /**
     * Get cache ttl value
     *
     * @param Response $response
     *
     * @return int
     */
    protected function getCacheTtl(Response $response)
    {
        if ($this->useHeaderTtl && $response->hasHeader('Cache-Control')) {
            $cacheControl =  $response->getHeader('Cache-Control')[0];

            if (preg_match('`max-age=(\d+)`', $cacheControl, $match)) {
                return intval($match[1]);
            }
        }

        return $this->defaultTtl;
    }

    /**
     * Cache response
     *
     * @param RequestInterface $request
     * @param Response         $response
     * @param int              $ttl
     *
     * @return mixed
     */
    protected function cacheResponse(RequestInterface $request, Response $response, $ttl = null)
    {
        if (!$this->isSupportedMethod($request)) {
            return;
        }

        $cacheTtl = $ttl ?: $this->getCachettl($response);

        // do we have a valid ttl to set the cache ?
        if ($cacheTtl <= 0) {
            return;
        }

        if (($statusCode = $response->getStatusCode()) >= 500 && !$this->cacheServerErrors) {
            return;
        }

        if (($statusCode < 500 && $statusCode >= 400) && !$this->cacheClientErrors) {
            return;
        }

        // copy response in array to  store
        $cached = new \SplFixedArray(5);
        $cached[0] = $statusCode;
        $cached[1] = $response->getHeaders();
        $cached[2] = $response->getBody()->__toString();
        $cached[3] = $response->getProtocolVersion();
        $cached[4] = $response->getReasonPhrase();

        return $this->cache->set(
            self::getKey($request),
            serialize($cached),
            $cacheTtl
        );
    }

    /**
     * Get response if available in cache
     *
     * @param RequestInterface $request
     *
     * @return Response|null
     */
    protected function getCached(RequestInterface $request)
    {
        if (!$this->isSupportedMethod($request)) {
            return null;
        }

        $cacheKey = self::getKey($request);
        if (is_null($cachedContent = $this->cache->get($cacheKey))) {
            return null;
        }

        $cached = unserialize($cachedContent);
        foreach ($cached as $value) {
            if (is_null($value)) {
                return null;
            }
        }

        // rebuild response with cache entry : status, headers, body, protocol version, reason
        $response = new Response($cached[0], $cached[1], $cached[2], $cached[3], $cached[4]);

        $response->cached = true;

        // set ttl information only on debug mode
        if ($this->debug) {
            $response->cacheTtl = $this->cache->ttl($cacheKey);
        }

        return $response;
    }


    /**
     * Check if request is in cache and return the response in this case
     * otherwise send request then cache the response
     *
     * @param RequestInterface $request
     * @param array            $options
     *
     * @return FulfilledPromise
     * @throws \Exception
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        if (is_null($this->cache)) {
            return parent::__invoke($request, $options);
        }

        //user want to force cache reload
        // so we remove existing cache
        if (array_key_exists('cache_force', $options)) {
            $this->cache->remove(self::getKey($request));
        }

        try {
            if ($response = $this->getCached($request)) {
                return new FulfilledPromise($response);
            }
        } catch (\Exception $e) {
            if (!$this->isIgnoreCacheErrors) {
                throw $e;
            }

            // Send event, when cache error is ignored.
            $ignoreCacheEvent = new GuzzleCacheErrorEvent($request);
            $ignoreCacheEvent->setException($e);
            $this->getEventDispatcher()->dispatch(GuzzleCacheErrorEvent::NAME_ERROR, $ignoreCacheEvent);
        }

        // no response in cache so we ask parent for response
        $result = parent::__invoke($request, $options);
        // then ask promise to cache the response when she's resolved
        $result->then(function (ResponseInterface $response) use ($request, $options) {
            //check if user want a specific cache duration
            $ttl = (!empty($options['cache_ttl'])) ? $options['cache_ttl'] : null;

            $this->cacheResponse($request, $response, $ttl);
        });

        return $result;
    }

    /**
     * Check if request method is cachable
     *
     * @param RequestInterface $request
     *
     * @return boolean
     */
    protected function isSupportedMethod(RequestInterface $request)
    {
        return in_array(strtoupper($request->getMethod()), self::$methods);
    }

    /**
     * Classes implementing this trait must to have an EventDispatcher.
     *
     * @return EventDispatcherInterface
     */
    abstract public function getEventDispatcher(): EventDispatcherInterface;
}
