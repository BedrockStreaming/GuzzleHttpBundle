<?php
namespace M6Web\Bundle\GuzzleHttpBundle\Middleware;

use GuzzleHttp\HandlerStack;

/**
 * Interface for middleware handlers
 */
interface MiddlewareInterface
{
    /**
     * Push function to middleware handler
     *
     * @param HandlerStack $stack
     *
     * @return HandlerStack
     */
    public function push(HandlerStack $stack);
}
