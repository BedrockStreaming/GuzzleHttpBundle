<?php

declare(strict_types=1);

namespace M6Web\Bundle\GuzzleHttpBundle\Middleware;

use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware to forward a configured header "host".
 */
class HostForwarderMiddleware implements MiddlewareInterface
{
    public function __construct(protected array $config)
    {
    }

    /**
     * Push function to middleware handler
     */
    public function push(HandlerStack $stack): HandlerStack
    {
        $stack->push(fn (callable $handler) => function (
            RequestInterface $request,
            array $options
        ) use ($handler) {
            if (isset($this->config['headers'])) {
                $lowercaseHeaders = array_change_key_case($this->config['headers']);
                if (isset($lowercaseHeaders['host'])) {
                    $request = $request->withHeader('host', $lowercaseHeaders['host']);
                }
            }

            return $handler($request, $options);
        }, 'hostForwarder_forward');

        return $stack;
    }
}
