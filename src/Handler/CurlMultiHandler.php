<?php

namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends guzzle CurlMultiHandler
 */
class CurlMultiHandler extends GuzzleCurlMultiHandler
{
    use CacheTrait;

    /** @var EventDispatcherInterface Event Dispatcher */
    protected $eventDispatcher;

    /**
     * CurlMultiHandler constructor.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $options)
    {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($options);
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
