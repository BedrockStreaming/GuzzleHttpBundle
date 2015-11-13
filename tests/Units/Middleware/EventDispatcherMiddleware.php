<?php
namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Middleware;

use atoum\test;
use M6Web\Bundle\GuzzleHttpBundle\Middleware\EventDispatcherMiddleware as Base;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpEvent;

/**
 * Class EventDispatcherMiddleware test
 */
class EventDispatcherMiddleware extends test
{
    public function testPush()
    {
        // Mock dispatcher
        $eventSend = null;
        $dispatcherMock = new \mock\Symfony\Component\EventDispatcher\EventDispatcherInterface();
        $dispatcherMock->getMockController()->dispatch = function($name, $event) use (&$eventSend) {
            $eventSend = $event;
        };

        // Mock HandlerStack
        $initCallable  = null;
        $eventCallable = null;
        $handlerStackMock = new \mock\GuzzleHttp\HandlerStack();
        $handlerStackMock->getMockController()->push = function($callable, $str) use (&$initCallable, &$eventCallable) {
            if ($str == "eventDispatcher_initEvent") {
                $initCallable  = $callable;
            }

            if ($str == "eventDispatcher_dispatch") {
                $eventCallable = $callable;
            }
        };

        // Response & request
        $requestMock  = new \mock\Psr\Http\Message\RequestInterface();
        $responseMock = new \mock\Psr\Http\Message\ResponseInterface();

        // Mock guzzle promise
        $successCallable = null;
        $errorCallable   = null;
        $promiseMock = new \mock\GuzzleHttp\Promise();
        $promiseMock->getMockController()->then = function($success, $error) use (&$successCallable, &$errorCallable) {
            $successCallable = $success;
            $errorCallable   = $error;
        };

        // Handler for init of event
        $handlerInit = function($request) {
            return $request;
        };

        // Handler for end of event
        $handlerEvent = function() use( $promiseMock) {
            return $promiseMock;
        };

        // 1st event : sucesss
        $this
            ->if($eventMid = new Base($dispatcherMock, 'id'))
            ->then
                ->object($eventMid->push($handlerStackMock))
                    ->isEqualTo($handlerStackMock)
                ->mock($handlerStackMock)
                    ->call('push')
                        ->twice()

                ->object($callableHandler = $initCallable($handlerInit))
                    ->isCallable()
                ->object($callableHandler($requestMock, []))
                    ->isEqualTo($requestMock)

                ->object($callableHandler = $eventCallable($handlerEvent))
                    ->isCallable()
                ->variable($callableHandler($requestMock, []))
                    ->isNull()
                ->mock($promiseMock)
                    ->call('then')
                        ->once()

                ->object($successCallable)
                    ->isCallable()
                ->object($successCallable($responseMock))
                    ->isEqualTo($responseMock)
                ->mock($dispatcherMock)
                    ->call('dispatch')
                        ->once()
                        ->withArguments(GuzzleHttpEvent::EVENT_NAME, $eventSend)
                            ->once()
                         ->withArguments(GuzzleHttpEvent::EVENT_ERROR_NAME)
                            ->never()

        // 2nd event : error
                ->object($eventMid->push($handlerStackMock))
                    ->isEqualTo($handlerStackMock)

                ->object($callableHandler = $initCallable($handlerInit))
                    ->isCallable()
                ->object($callableHandler($requestMock, []))
                    ->isEqualTo($requestMock)

                ->object($callableHandler = $eventCallable($handlerEvent))
                    ->isCallable()
                ->variable($callableHandler($requestMock, []))
                    ->isNull()
                ->mock($promiseMock)
                    ->call('then')
                        ->twice()

                ->object($errorCallable)
                    ->isCallable()
                ->object($errorCallable("reason"))
                    ->isEqualTo($eventMid)
                ->mock($dispatcherMock)
                    ->call('dispatch')
                        ->withArguments(GuzzleHttpEvent::EVENT_ERROR_NAME, $eventSend)
                            ->once()
            ;
    }
}