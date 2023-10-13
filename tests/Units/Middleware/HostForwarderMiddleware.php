<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\tests\Units\Middleware;

use M6Web\Bundle\GuzzleHttpBundle\Middleware\HostForwarderMiddleware as Base;

/**
 * Class HostForwarderMiddleware test
 */
class HostForwarderMiddleware extends \atoum
{
    /**
     * @dataProvider pushHostForwarderDataProvider
     */
    public function testPushHostForwarder(array $configuration, int $expectedNumberOfCalls)
    {
        // Mock HandlerStack
        $eventCallable = null;
        $handlerStackMock = new \mock\GuzzleHttp\HandlerStack();
        $handlerStackMock->getMockController()->push = function ($callable, $str) use (&$eventCallable) {
            if ($str == 'hostForwarder_forward') {
                $eventCallable = $callable;
            }
        };

        // Response & request
        $requestMock = new \mock\Psr\Http\Message\RequestInterface();

        // Handler for end of event
        $handlerEvent = function () {
            return new \mock\GuzzleHttp\Promise();
        };

        $this
            ->if($hostForwarderMiddleware = new Base($configuration))
            ->then
                ->object($hostForwarderMiddleware->push($handlerStackMock))
                    ->isEqualTo($handlerStackMock)
                ->mock($handlerStackMock)
                    ->call('push')
                        ->once()
                ->object($callableHandler = $eventCallable($handlerEvent))
                    ->isCallable()
                ->variable($callableHandler($requestMock, []))
                    ->isNotNull()
                ->mock($requestMock)
                    ->call('withHeader')
                        ->exactly($expectedNumberOfCalls)
                        ->withArguments('host', 'my_configured_host')
        ;
    }

    protected function pushHostForwarderDataProvider(): array
    {
        return [
            [
                'configuration' => [
                    'headers' => [
                        'host' => 'my_configured_host',
                    ],
                ],
                'expectedNumberOfCalls' => 1,
            ],
            [
                'configuration' => [
                    'headers' => [
                        'HOST' => 'my_configured_host',
                    ],
                ],
                'expectedNumberOfCalls' => 1,
            ],
            [
                'configuration' => [
                    'headers' => [],
                ],
                'expectedNumberOfCalls' => 0,
            ],
        ];
    }
}
