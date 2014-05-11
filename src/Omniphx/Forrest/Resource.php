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

	public function __construct(ClientInterface $client, SessionInterface $session){
		$this->client = $client;
		$this->session = $session;
	}

	public function request($pURI,$pOptions=[]){
        $token = $this->session->getToken();
        
        $accessToken = $token['access_token'];
        $instanceURL = $token['instance_url'];
        $url = $instanceURL . $pURI;

        if(!isset($pOptions['format'])) Throw new \Exception("No format is specified", 1);
        $format = $pOptions['format'];

        if(!isset($pOptions['method'])) Throw new \Exception("No method is specified", 1);
        $method = $pOptions['method'];

        $headers = ["Authorization" => "OAuth $accessToken"];

        //If format is xml, then add extra header
        if($format == 'xml') $headers['Accept'] = 'application/xml';

        $options = ["headers" => $headers];

        $request = $this->client->createRequest($method,$url,$options);
        $response = $this->client->send($request);

        if($pOptions['format'] == 'xml'){
            return $response->xml();
        } else {
            return $response->json();
        }
    }

}