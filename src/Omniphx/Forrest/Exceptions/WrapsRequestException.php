<?php

namespace Omniphx\Forrest\Exceptions;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Wraps a Guzzle RequestException while keeping its response reachable on
 * every supported Guzzle major.
 *
 * Guzzle 6/7 keep the response on RequestException itself. Guzzle 8 moves it
 * to the ResponseException subclass (whose constructor is final) and drops
 * the response argument from RequestException's constructor, so the response
 * is stored here and exposed through the same getResponse()/hasResponse()
 * methods callers already use.
 */
trait WrapsRequestException
{
    /**
     * @var ResponseInterface|null
     */
    private $salesforceResponse;

    public function __construct($message, RequestException $e)
    {
        $response = static::responseFrom($e);

        if (class_exists(ResponseException::class)) {
            parent::__construct($message, $e->getRequest(), $response ? $response->getStatusCode() : 0, $e);
        } else {
            parent::__construct($message, $e->getRequest(), $response, $e);
        }

        $this->salesforceResponse = $response;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->salesforceResponse;
    }

    public function hasResponse(): bool
    {
        return $this->salesforceResponse !== null;
    }

    /**
     * The response carried by a Guzzle RequestException, if any.
     */
    public static function responseFrom(RequestException $e): ?ResponseInterface
    {
        if ($e instanceof ResponseException) {
            return $e->getResponse();
        }

        if (method_exists($e, 'hasResponse') && $e->hasResponse()) {
            return $e->getResponse();
        }

        return null;
    }
}
