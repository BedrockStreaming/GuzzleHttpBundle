<?php

namespace M6Web\Bundle\GuzzleHttpBundle\EventDispatcher;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class AbstractGuzzleHttpEvent
 */
abstract class AbstractGuzzleHttpEvent extends Event
{
    public const EVENT_NAME = 'm6web.guzzlehttp';
    public const EVENT_ERROR_NAME = 'm6web.guzzlehttp.error';

    /** @var float Command start time */
    protected $executionStart;

    /** @var float Command execution time */
    protected $executionTime;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var \Exception|null */
    protected $reason;

    /** @var string */
    protected $clientId;

    /**
     * Set request
     *
     * @param Request $request
     *
     * @return static
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Return request
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Set Response
     *
     * @return static
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Return response
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Set reason
     *
     * @return static
     */
    public function setReason(\Exception $reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Return reason
     */
    public function getReason(): ?\Exception
    {
        return $this->reason;
    }

    public function getExecutionStart(): float
    {
        return $this->executionStart;
    }

    /**
     * Set execution start of a request
     *
     * @return static
     */
    public function setExecutionStart()
    {
        $this->executionStart = microtime(true);

        return $this;
    }

    /**
     * Stop the execution of a request
     * and set the request execution time
     *
     * @return static
     */
    public function setExecutionStop()
    {
        $this->executionTime = microtime(true) - $this->executionStart;

        return $this;
    }

    public function getExecutionTime(): float
    {
        return $this->executionTime;
    }

    /**
     * Return execution time in milliseconds
     */
    public function getTiming(): float
    {
        return $this->getExecutionTime() * 1000;
    }

    /**
     * Get client ID
     */
    public function getClientId(): string
    {
        return $this->clientId;
    }

    /**
     * Set client ID
     *
     * @param string $clientId
     *
     * @return static
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this;
    }
}
