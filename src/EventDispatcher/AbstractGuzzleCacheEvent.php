<?php

namespace M6Web\Bundle\GuzzleHttpBundle\EventDispatcher;

use Psr\Http\Message\RequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class AbstractGuzzleCacheEvent
 */
abstract class AbstractGuzzleCacheEvent extends Event
{
    /** @const string Name of standard event */
    public const NAME = 'm6web.guzzlecache';

    /** @const string Name of Error event */
    public const NAME_ERROR = 'm6web.guzzlecache.error';

    /** @var RequestInterface */
    protected $request;

    /** @var \Exception|null */
    protected $exception;

    /**
     * GuzzleCacheEvent constructor.
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Get Request
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Set Exception
     */
    public function setException(\Exception $e)
    {
        $this->exception = $e;
    }

    /**
     * Get Exception if isset
     */
    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    /**
     * Get domain from Request, with graphite compatibility (remove point)
     */
    public function getDomain(): string
    {
        return str_replace('.', '_', $this->request->getUri()->getHost());
    }
}
