<?php namespace Omniphx\Forrest;

use Omniphx\Forrest\Interfaces\ResourceInterface;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Exceptions\MissingTokenException;

class Resource implements ResourceInterface {

	/**
     * Interface for HTTP Client
     * @var GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * Interface for Session calls
     * @var Omniphx\Forrest\Interfaces\SessionInterface
     */
    protected $session;

    /**
     * Default settings for the resource request
     * @var array
     */
    protected $defaults;

    /**
     * Constructor
     * @param ClientInterface  $client   HTTP Request client
     * @param SessionInterface $session  Session handler
     * @param array            $defaults Config defaults
     */
    public function __construct(ClientInterface $client, SessionInterface $session, array $defaults){
		$this->client = $client;
		$this->session = $session;
        $this->defaults = $defaults;
	}

    /**
     * Method creates the request for the intended resource
     * @param  string $pURI     Resource URI
     * @param  array  $pOptions Options for type of request and format of request/response
     * @return array            Response in the format of specifed format
     */
    public function request($pURI, array $pOptions){

        $instanceURL = $this->session->getToken()['instance_url'];
        $url = $instanceURL . $pURI;

        $options = array_replace_recursive($this->defaults, $pOptions);

        $format = $options['format'];
        $method = $options['method'];

        $parameters['headers'] = $this->setHeaders($options);
        if(isset($options['body'])) $parameters['body'] = $this->setBody($options);

        $request = $this->client->createRequest($method,$url,$parameters);

        $response = $this->client->send($request);

        if($format == 'json'){
            return $response->json();
        } else if($format == 'xml'){
            return $response->xml();
        } else {
            return $response;
        }
    }

    /**
     * Set the headers for the request
     * @param array $options
     */
    public function setHeaders(array $options){
        $format = $options['format'];

        $accessToken = $this->session->getToken()['access_token'];
        $headers['Authorization'] = "OAuth $accessToken";

        if($format == 'json'){
            $headers['Accept'] = 'application/json';
            $headers['content-type'] = 'application/json';
        } else if($format == 'xml'){
            $headers['Accept'] = 'application/xml';
            $headers['content-type'] = 'application/xml';
        } else if($format == 'urlencoded'){
            $headers['Accept'] = 'application/x-www-form-urlencoded';
            $headers['content-type'] = 'application/x-www-form-urlencoded';
        }

        return $headers;
    }

    /**
     * Set the body for the request
     * @param array $options
     */
    public function setBody(array $options){
        $format = $options['format'];
        $data   = $options['body'];

        if($format == 'json'){
            $body = json_encode($data);
        } else if($format == 'xml'){
            $body = $data;
        }

        return $body;
    }

    /**
     * Returns the response in the specified format
     * @param  GuzzleHttp\Message\ResponseInterface $response
     * @param  string $format
     * @return response
     */
    public function responseFormat($response,$format){
        if($format == 'json'){
            return $response->json();
        } else if($format == 'xml'){
            return $response->xml();
        } else {
            return $response;
        }
    }

}