<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlHandler as GuzzleCurlHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Extends the guzzle curl handler
 */
class CurlHandler extends GuzzleCurlHandler
{
    use CacheTrait;

    /** @var EventDispatcherInterface Event Dispatcher */
    protected $eventDispatcher;

    /**
     * CurlHandler constructor.
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $options = [])
    {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($options);
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
