<?php namespace Omniphx\Forrest;

use Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Input;
use Illuminate\Session;
use Omniphx\Forrest\RequestServices\CurlClient;

class Forrest {

	protected $requestClient;

	public function __construct(CurlClient $requestClient){
		$this->requestClient = $requestClient; 
	}

	protected $clientId;
	protected $clientSecret;
	protected $redirectURI;
	protected $loginURI;

	protected function getClientId(){
		return $this->clientId = Config::get('forrest::sfkeys.clientId');
	}

	protected function getClientSecret(){
		return $this->clientSecret = Config::get('forrest::sfkeys.clientSecret');
	}

	protected function getRedirectURI(){
		return $this->redirectURI = Config::get('forrest::sfkeys.redirectURI');
	}

	protected function getLoginURI(){
		return $this->loginURI = Config::get('forrest::sfkeys.loginURI');
	}

	public function authenticate()
    {
    	$clientId = $this->getClientId();
    	$redirectURI = $this->getRedirectURI();
    	$loginURI = $this->getLoginURI();

        return Redirect::to($loginURI
        	. "/services/oauth2/authorize?response_type=code&client_id="
        	. $clientId
        	. "&redirect_uri="
        	. urlencode($redirectURI));
    }

    public function callback()
    {
    	$clientId = $this->getClientId();
    	$clientSecret = $this->getClientSecret();
    	$redirectURI = $this->getRedirectURI();
    	$loginURI = $this->getLoginURI();
        $tokenURL = $loginURI . "/services/oauth2/token";
        
        $code = Input::get('code');
        
        if (!isset($code) || $code == "") {
            die("Error - code parameter missing from request!");
        }
        
        $params = "code=" . $code
            . "&grant_type=authorization_code"
            . "&client_id=" . $clientId
            . "&client_secret=" . $clientSecret
            . "&redirect_uri=" . urlencode($redirectURI);
        
        $response = $this->requestClient->postRequest($tokenURL, $params);
        
        $accessToken = $response['access_token'];
        $instanceURL = $response['instance_url'];
        
        if (!isset($accessToken) || $accessToken == "") {
            die("Error - access token missing from response");
        }
        
        if (!isset($instanceURL) || $instanceURL == "") {
            die("Error - instance URL missing from response");
        }

        Session::put('access_token', $accessToken);
        Session::put('instance_url', $instanceURL);
        
        return Redirect::to('home');

    }

	public static function query(){}

	public static function describe(){}

	public static function search(){}

	public static function sObjects(){}

}