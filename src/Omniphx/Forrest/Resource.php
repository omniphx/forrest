<?php namespace Omniphx\Forrest;

use Omniphx\Forrest\Interfaces\ResourceInterface;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;

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
        $token = $this->session->get('token');
        $accessToken = $token['access_token'];
        $instanceURL = $token['instance_url'];

        $url = $instanceURL . $pURI;

        $headers = ["Authorization" => "OAuth $accessToken"];
        $options = ["headers" => $headers];

        $request = $this->client->createRequest($pOptions['method'],$url,$options);

        $response = $this->client->send($request);

        if($pOptions['format'] == 'XML'){
            return $response->xml();
        } else {
            return $response->json();
        }
    }

}