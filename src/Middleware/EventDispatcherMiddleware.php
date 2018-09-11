<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Middleware;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpEvent;

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
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->initEvent($request);

            return $request;
        }), 'eventDispatcher_initEvent');

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
     * Initialize event
     *
     * @param RequestInterface $request
     */
    protected function initEvent(RequestInterface $request)
    {
        $event = new GuzzleHttpEvent();
        $event->setExecutionStart();
        $event->setRequest($request);
        $event->setClientId($this->clientId);

        $this->events[$this->getEventKey($request)] = $event;
    }

    /**
     * Dispatch event
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     */
    protected function sendEvent(RequestInterface $request, ResponseInterface $response)
    {
        $key = $this->getEventKey($request);
        $event = $this->events[$key];

        unset($this->events[$key]);

        $event->setExecutionStop();
        $event->setResponse($response);
        $this->eventDispatcher->dispatch(GuzzleHttpEvent::EVENT_NAME, $event);
    }

    /**
     * Dispatch event
     *
     * @param RequestInterface $request
     * @param mixed            $reason
     */
    protected function sendErrorEvent(RequestInterface $request, $reason)
    {
        $key = $this->getEventKey($request);
        $event = $this->events[$key];

        unset($this->events[$key]);

        $event->setExecutionStop();
        $event->setReason($reason);
        $this->eventDispatcher->dispatch(GuzzleHttpEvent::EVENT_ERROR_NAME, $event);
    }
}
