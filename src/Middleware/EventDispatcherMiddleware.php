<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Middleware;

use GuzzleHttp\HandlerStack;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpErrorEvent;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpEvent;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Handler for event dispatching
 */
class EventDispatcherMiddleware implements MiddlewareInterface
{

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $events;

    /**
     * @var string
     */
    protected $clientId;

    /**
     * Constructor
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $clientId
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, $clientId)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->events = [];
        $this->clientId = $clientId;
    }

    /**
     * Push function to middleware handler
     *
     * @param HandlerStack $stack
     *
     * @return HandlerStack
     */
    public function push(HandlerStack $stack)
    {
        $stack->push(function (callable $handler) {
            return function (
                RequestInterface $request,
                array $options
            ) use ($handler) {
                $promise = $handler($request, $options);

                return $promise->then(
                    function (ResponseInterface $response) use ($request) {
                        $this->sendEvent($request, $response);

                        return $response;
                    },
                    function (\Exception $reason) use ($request) {
                        $this->sendErrorEvent($request, $reason);

                        throw $reason;
                    }
                );
            };
        }, 'eventDispatcher_dispatch');

        return $stack;
    }

    /**
     * Get key for request object
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function getEventKey(RequestInterface $request)
    {
        return spl_object_hash($request);
    }

    /**
     * Dispatch event
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     */
    protected function sendEvent(RequestInterface $request, ResponseInterface $response)
    {
        $event = new GuzzleHttpEvent();
        $event->setExecutionStart();
        $event->setRequest($request);
        $event->setClientId($this->clientId);
        $event->setExecutionStop();
        $event->setResponse($response);
        $this->eventDispatcher->dispatch($event, GuzzleHttpEvent::EVENT_NAME);
    }

    /**
     * Dispatch event
     *
     * @param RequestInterface $request
     * @param mixed            $reason
     */
    protected function sendErrorEvent(RequestInterface $request, $reason)
    {
        $event = new GuzzleHttpEvent();
        $event->setExecutionStart();
        $event->setRequest($request);
        $event->setClientId($this->clientId);
        $event->setExecutionStop();
        $event->setReason($reason);
        $this->eventDispatcher->dispatch($event, GuzzleHttpErrorEvent::EVENT_ERROR_NAME);
    }
}
