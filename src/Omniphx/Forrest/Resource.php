<?php namespace Omniphx\Forrest;

use Omniphx\Forrest\Interfaces\ResourceInterface;
use GuzzleHttp\Exception\RequestException;

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

        if($options['debug'] == true){
            $response = $this->debug($request);
            if(is_object($response))
            {
                return $this->responseFormat($response,$format);
            }
        } else {
            $response = $this->client->send($request);

            return $this->responseFormat($response,$format);
        }
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

    private function debug($request)
    {
        try {
            return $this->client->send($request);
        } catch (RequestException $e) {
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

}