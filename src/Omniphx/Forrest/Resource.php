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

	public function __construct(ClientInterface $client, SessionInterface $session, $defaults = []){
		$this->client = $client;
		$this->session = $session;
        $this->defaults = $defaults;
	}

	public function request($pURI,$pOptions=[]){
        $token = $this->session->getToken();
        
        $accessToken = $token['access_token'];
        $instanceURL = $token['instance_url'];
        $url = $instanceURL . $pURI;

        $options = array_replace_recursive($this->defaults, $pOptions);

        $format = $options['format'];
        $method = $options['method'];

        $parameters['headers']['Authorization'] = "OAuth $accessToken";
        $format == 'xml' ? $parameters['headers']['Accept'] = 'application/xml':0;

        $request = $this->client->createRequest($method,$url,$parameters);
        $response = $this->client->send($request);

        if($format == 'xml'){
            return $response->xml();
        } else {
            return $response->json();
        }
    }

}