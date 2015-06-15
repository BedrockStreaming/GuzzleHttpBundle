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
     * Constructor
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {

        $this->eventDispatcher = $eventDispatcher;
        $this->events = [];
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
                    }
                );
            };
        }, 'eventDispatcher_dispatch');

        return $stack;
    }

    /**
     * Get key for request object
     *
     * @param Request $request
     *
     * @return string
     */
    protected function getEventKey(Request $request)
    {
        return spl_object_hash($request);
    }

    /**
     * Initialize event
     *
     * @param Request $request
     */
    protected function initEvent(Request $request)
    {
        $event = new GuzzleHttpEvent();
        $event->setExecutionStart();
        $event->setRequest($request);

        $this->events[$this->getEventKey($request)] = $event;
    }

    /**
     * Dispatch event
     *
     * @param Request  $request
     * @param Response $response
     */
    protected function sendEvent(Request $request, Response $response)
    {
        $key = $this->getEventKey($request);
        $event = $this->events[$key];

        unset($this->events[$key]);

        $event->setExecutionStop();
        $event->setResponse($response);
        $this->eventDispatcher->dispatch(GuzzleHttpEvent::EVENT_NAME, $event);
    }
}