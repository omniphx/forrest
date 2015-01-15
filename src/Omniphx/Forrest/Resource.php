<?php namespace Omniphx\Forrest;

use GuzzleHttp\Exception\RequestException;
use Omniphx\Forrest\Exceptions\SalesforceException;
use Omniphx\Forrest\Exceptions\TokenExpiredException;

abstract class Resource {

    /**
     * HTTP request client
     * @var Client
     */
    protected $client;

    /**
     * Config options
     * @var array
     */
    protected $settings;

    /**
     * Storage handler
     * @var storage
     */
    protected $storage;

    /**
     * Reqeust headers
     * @var Array
     */
    private $headers;

    /**
     * Method returns the response for the requested resource
     * @param  string $pURI 
     * @param  array  $pOptions
     * @return mixed
     */
    protected function requestResource($pURL, array $pOptions)
    {
        $options = array_replace_recursive($this->settings['defaults'], $pOptions);

        $format = $options['format'];
        $method = $options['method'];

        $this->setHeaders($options);
        
        $parameters['headers'] = $this->headers;

        if (isset($options['body'])) {
            $parameters['body'] = $this->formatBody($options);
        }

        $request = $this->client->createRequest($method,$pURL,$parameters);

        try {
            $response = $this->client->send($request);
        } catch(RequestException $e) {
            $this->assignExceptions($e);
        }

        return $this->responseFormat($response,$format);
        
    }

    /**
     * Set the headers for the request
     * @param array $options
     * @return array $headers
     */
    private function setHeaders(array $options)
    {
        $format = $options['format'];

        $authToken = $this->storage->getToken();

        $accessToken = $authToken['access_token'];
        $tokenType   = $authToken['token_type'];

        $this->headers['Authorization'] = "$tokenType $accessToken";

        $this->setRequestFormat($options['format']);
        $this->setCompression($options);
    }

    /**
     * Format the body for the request
     * @param array $options
     * @return array $body
     */
    private function formatBody(array $options)
    {
        $format = $options['format'];
        $data   = $options['body'];

        if ($format == 'json') {
            $body = json_encode($data);
        }
        else if($format == 'xml') {
            $body = urlencode($data);
        }

        return $body;
    }

    //Need to think through this for it to work
    private function setRequestFormat($format)
    {
        if ($format == 'json') {
            $this->headers['Accept'] = 'application/json';
            $this->headers['Content-Type'] = 'application/json';
        }
        else if ($format == 'xml') {
            $this->headers['Accept'] = 'application/xml';
            $this->headers['Content-Type'] = 'application/xml';
        }
        else if ($format == 'urlencoded') {
            $this->headers['Accept'] = 'application/x-www-form-urlencoded';
            $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
    }

    private function setCompression($options)
    {
        if ($options['compression'] == true) {
            $this->headers['Accept-Encoding'] = $options['compressionType'];
            $this->headers['Content-Encoding'] = $options['compressionType'];
        }
    }

    /**
     * Returns the response in the configured  format
     * @param  Response $response
     * @param  string $format
     * @return mixed $response
     */
    private function responseFormat($response,$format)
    {
        if ($format == 'json') {
            return $response->json();
        }
        else if ($format == 'xml') {
            return $response->xml();
        }

        return $response;
    }

    /**
     * Method will elaborate on RequestException
     * @param  GuzzleHttp\Exception\ClientException $e
     * @return mixed
     */
    private function assignExceptions($e)
    {
        if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '401') {
            throw new TokenExpiredException(sprintf("Salesforce token has expired"));
        } else if($e->hasResponse()){
            throw new SalesforceException(sprintf("Salesforce response error: %s",$e->getResponse()));
        } else {
            throw new SalesforceException(sprintf("Invalid request: %s",$e->getRequest()));
        }
    }
}