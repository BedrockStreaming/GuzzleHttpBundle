<?php
namespace M6Web\Bundle\GuzzleHttpBundle\DataCollector;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\GuzzleHttpEvent;

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
        $this->data['guzzleHttp'] = new \SplQueue();
    }

    /**
     * Collect the data
     * @param Request    $request   The request object
     * @param Response   $response  The response object
     * @param \Exception $exception An exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
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
     *
     * @param GuzzleHttpEvent $event
     */
    public function onGuzzleHttpCommand(GuzzleHttpEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $data = [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'responseCode' => $response->getStatusCode(),
            'responseReason' => $response->getReasonPhrase(),
            'executionTime' => $event->getExecutionTime(),
            'curl' => [
                'redirectCount' => $response->curlInfo['redirect_count'],
                'redirectTime' => $response->curlInfo['redirect_time']
            ]

        ];

        $this->data['guzzleHttp']->enqueue($data);
    }

    /**
     * Return GuzzleHttp command list
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->data['guzzleHttp'];
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
     * Return average time spent by cassandra command
     *
     * @return float
     */
    public function getAvgExecutionTime()
    {
        $totalExecutionTime = $this->getTotalExecutionTime();

        return ($totalExecutionTime) ? ($totalExecutionTime / count($this->getCommands()) ) : 0;
    }
}