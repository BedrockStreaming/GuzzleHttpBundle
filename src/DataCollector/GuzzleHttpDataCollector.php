<?php

namespace M6Web\Bundle\GuzzleHttpBundle\DataCollector;

use GuzzleHttp\Client as GuzzleHttpClient;
use M6Web\Bundle\GuzzleHttpBundle\EventDispatcher\AbstractGuzzleHttpEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Collect information about guzzlehttp client
 */
class GuzzleHttpDataCollector extends DataCollector
{
    /** @var GuzzleHttpClient[] */
    private $clients = [];

    public function __construct()
    {
        $this->reset();
    }

    public function registerClient(string $name, GuzzleHttpClient $client)
    {
        $this->clients[$name] = $client;
        $this->data['clients'][$name]['requests'] = new \SplQueue();
        $this->data['clients'][$name]['errors'] = 0;
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
        $this->data = [
            'clients' => [],
            'request_count' => 0,
            'error_count' => 0,
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
        $client = 'm6web_guzzlehttp';
        if ($event->getClientId() !== 'default') {
            $client = $client.'_'.$event->getClientId();
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $statusCode = $response->getStatusCode();

        if ($statusCode >= Response::HTTP_BAD_REQUEST) {
            $this->data['error_count']++;
            $this->data['clients'][$client]['errors']++;
        }

        $data = [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'responseCode' => $statusCode,
            'responseReason' => $response->getReasonPhrase(),
            'options' => $this->cloneVar($request->getHeaders()),
            'response' => $this->cloneVar($response->getHeaders()),
        ];

        $this->data['request_count']++;
        $this->data['clients'][$client]['requests']->enqueue($data);
    }

    public function getClients(): array
    {
        return $this->data['clients'] ?? [];
    }

    public function getRequestCount(): int
    {
        return $this->data['request_count'] ?? 0;
    }

    public function getErrorCount(): int
    {
        return $this->data['error_count'] ?? 0;
    }
}
