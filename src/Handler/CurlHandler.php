<?php
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
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $options
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $options)
    {
        $this->eventDispatcher = $eventDispatcher;

        parent::__construct($options);
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
