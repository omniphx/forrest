<?php namespace Omniphx\Forrest;

use GuzzleHttp\Exception\RequestException;
use Omniphx\Forrest\Interfaces\ResourceInterface;
use Omniphx\Forrest\Exceptions\SalesforceException;
use Omniphx\Forrest\Exceptions\TokenExpiredException;

abstract class Resource implements ResourceInterface {

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
     * Session handler
     * @var Session
     */
    protected $session;

    /**
     * Method returns the response for the requested resource
     * @param  string $pURI 
     * @param  array  $pOptions
     * @return mixed
     */
    public function requestResource($pURL, array $pOptions)
    {
        $options = array_replace_recursive($this->settings['defaults'], $pOptions);

        $format = $options['format'];
        $method = $options['method'];

        $parameters['headers'] = $this->setHeaders($options);

        if (isset($options['body'])) {
            $parameters['body'] = $this->setBody($options);
        }

        $request = $this->client->createRequest($method,$pURL,$parameters);

        try {
            $response = $this->client->send($request);
        } catch(RequestException $e) {

            if($options['debug']){
                $this->debug($e);
            } else if ($e->hasResponse() && $e->getResponse()->getStatusCode() == '401') {
                throw new TokenExpiredException(sprintf("Salesforce token has expired"));
            } else if($e->hasResponse()){
                throw new SalesforceException(sprintf("Request failed: %s",$e->getResponse()));
            } else {
                throw new SalesforceException(sprintf("Invalid request: %s",$e->getRequest()));
            }

        }

        return $this->responseFormat($response,$format);
        
    }

    /**
     * Set the headers for the request
     * @param array $options
     * @return array $headers
     */
    public function setHeaders(array $options)
    {
        $format = $options['format'];

        $authToken = $this->session->getToken();

        $accessToken = $authToken['access_token'];
        $tokenType   = $authToken['token_type'];

        $headers['Authorization'] = "$tokenType $accessToken";

        if ($format == 'json') {
            $headers['Accept'] = 'application/json';
            $headers['Content-Type'] = 'application/json';
        }
        else if ($format == 'xml') {
            $headers['Accept'] = 'application/xml';
            $headers['Content-Type'] = 'application/xml';
        }
        else if ($format == 'urlencoded') {
            $headers['Accept'] = 'application/x-www-form-urlencoded';
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        }
        return $headers;
    }

    /**
     * Set the body for the request
     * @param array $options
     * @return array $body
     */
    public function setBody(array $options)
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

    /**
     * Returns the response in the configured  format
     * @param  Response $response
     * @param  string $format
     * @return mixed $response
     */
    public function responseFormat($response,$format)
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
     * If users has debug = true in config, this will output the failed request.
     * @param  RequestException $request
     * @return void
     */
    private function debug($error)
    {
        echo "<pre>";
        echo "Request\n";
        echo "-------\n";
        echo $e->getRequest() . "\n";
        if ($e->hasResponse()) {
            echo "\nResponse\n";
            echo "--------\n";
            echo $e->getResponse() . "\n";
        }
        echo "</pre>";
    }

}