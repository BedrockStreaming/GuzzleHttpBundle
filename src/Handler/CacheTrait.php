<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use M6Web\Bundle\GuzzleHttpBundle\Cache\CacheInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Promise\FulfilledPromise;

trait CacheTrait
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var int
     */
    protected $defaultTtl;

    /**
     * @var boolean
     */
    protected $useHeaderTtl;

    /**
     * @var boolean
     */
    protected $debug = false;

    /**
     * @var array
     */
    protected static $methods = ['GET', 'HEAD', 'OPTIONS'];

    /**
     * @param CacheInterface $cache
     * @param int            $defaultTtl
     * @param boolean        $useHeaderTtl
     */
    public function setCache(CacheInterface $cache, $defaultTtl, $useHeaderTtl)
    {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
        $this->useHeaderTtl = $useHeaderTtl;
    }

    /**
     * Set the debug mode
     *
     * @param boolean $debug
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
        return md5($request->getMethod().$request->getUri());
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
     * @return booelan
     */
    protected function cacheResponse(RequestInterface $request, Response $response, $ttl = null)
    {
        if (!$this->isSupportedMethod($request)) {
            return;
        }

        // copy response in array to  store
        $cached = new \SplFixedArray(5);
        $cached[0] = $response->getStatusCode();
        $cached[1] = $response->getHeaders();
        $cached[2] = $response->getBody()->__toString();
        $cached[3] = $response->getProtocolVersion();
        $cached[4] = $response->getReasonPhrase();



        return $this->cache->set(
            self::getKey($request),
            serialize($cached),
            $ttl ?: $this->getCachettl($response)
        );
    }

    /**
     * Get resposne if available in cache
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

        if ($response = $this->getCached($request)) {
            return new FulfilledPromise($response);
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
}
