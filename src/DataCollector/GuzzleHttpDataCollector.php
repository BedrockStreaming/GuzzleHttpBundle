<?php

namespace M6Web\Bundle\GuzzleHttpBundle\DataCollector;

use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\AbstractGuzzleHttpEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collect information about guzzlehttp client
 */
class GuzzleHttpDataCollector extends DataCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Collect the data
     *
     * @param Request    $request   The request object
     * @param Response   $response  The response object
     * @param \Exception $exception An exception
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
    }

    /**
     * Reset the data colector
     */
    public function reset()
    {
        $this->data['guzzleHttp'] = [
            'commands' => new \SplQueue(),
            'has5x' => false,
            'has4x' => false,
        ];
    }

    /**
     * Return the name of the collector
     *
     * @return string data collector name
     */
    public function getName()
    {
        return 'guzzlehttp';
    }

    /**
     * Collect data for GuzzleHttp
     */
    public function onGuzzleHttpCommand(AbstractGuzzleHttpEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        if ($statusCode > 499) {
            $this->data['guzzleHttp']['has5x'] = true;
        }

        if ($statusCode > 399 && $statusCode < 500) {
            $this->data['guzzleHttp']['has4x'] = true;
        }

        $data = [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'responseCode' => $statusCode,
            'responseReason' => $response->getReasonPhrase(),
            'executionTime' => $event->getExecutionTime(),
            'curl' => [
                'redirectCount' => (isset($response->curlInfo['redirect_count'])) ? $response->curlInfo['redirect_count'] : 0,
                'redirectTime' => (isset($response->curlInfo['redirect_time'])) ? $response->curlInfo['redirect_time'] : 0,
            ],
            'cache' => (isset($response->cached)) ? 1 : 0,
            'cacheTtl' => (isset($response->cacheTtl)) ? $response->cacheTtl : 0,
        ];

        $this->data['guzzleHttp']['commands']->enqueue($data);
    }

    /**
     * Return GuzzleHttp command list
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->data['guzzleHttp']['commands'];
    }

    /**
     * Return true error 400 occurred
     *
     * @return bool
     */
    public function has4x()
    {
        return $this->data['guzzleHttp']['has4x'];
    }

    /**
     * Return true error 500 occurred
     *
     * @return bool
     */
    public function has5x()
    {
        return $this->data['guzzleHttp']['has5x'];
    }

    /**
     * Return the total time spent by guzzlehttp
     *
     * @return float
     */
    public function getTotalExecutionTime()
    {
        return array_reduce(iterator_to_array($this->getCommands()), function ($time, $value) {
            $time += $value['executionTime'];

            return $time;
        });
    }

    /**
     * Return average time spent by guzzlehttp command
     *
     * @return float
     */
    public function getAvgExecutionTime()
    {
        $totalExecutionTime = $this->getTotalExecutionTime();

        return ($totalExecutionTime) ? ($totalExecutionTime / count($this->getCommands())) : 0;
    }

    /**
     * Return total cache hits
     *
     * @return int
     */
    public function getCacheHits()
    {
        return array_reduce(iterator_to_array($this->getCommands()), function ($hits, $value) {
            $hits += $value['cache'];

            return $hits;
        });
    }

    /**
     * Return total cache hits
     *
     * @return int
     */
    public function getRedirects()
    {
        return array_reduce(iterator_to_array($this->getCommands()), function ($redirect, $value) {
            $redirect += $value['curl']['redirectCount'];

            return $redirect;
        });
    }

    /**
     * Return the total time spent by redirection
     *
     * @return float
     */
    public function getTotalRedirectionTime()
    {
        return array_reduce(iterator_to_array($this->getCommands()), function ($time, $value) {
            $time += $value['curl']['redirectTime'];

            return $time;
        });
    }

    /**
     * Return average time spent by redirection
     *
     * @return float
     */
    public function getAvgRedirectionTime()
    {
        $totalExecutionTime = $this->getTotalRedirectionTime();

        return ($totalExecutionTime) ? ($totalExecutionTime / count($this->getRedirects())) : 0;
    }
}
