<?php namespace Omniphx\Forrest;

use Config;

class Forrest {

	protected $clientId;
	protected $clientSecret;
	protected $redirectURI;
	protected $loginURI;
	
	public static function greeting(){
		return Config::get('forrest::sfkeys.clientId');
	}

	

}