<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Handler;

use GuzzleHttp\Handler\CurlMultiHandler as GuzzleCurlMultiHandler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;

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
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param array                    $options
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, array $options)
    {
        $this->eventDispatcher = LegacyEventDispatcherProxy::decorate($eventDispatcher);

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
