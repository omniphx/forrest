<?php namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Exceptions\MissingTokenException;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Session;
use Crypt;

class LaravelSession implements SessionInterface {

	public function get($key){
		$value = Session::get($key);
		if(isset($value)){
			return Session::get($key);
		}
		
		Throw new MissingKeyException(sprintf("No value for requested key: %s",$key));
	}

	public function put($key, $value){
		return Session::put($key, $value);
	}

	public function putToken($token){
		$encyptedToken = Crypt::encrypt($token);
		return Session::put('token', $encyptedToken);
	}

	public function getToken(){
		$token = Session::get('token');
		if(isset($token)){
			return Crypt::decrypt($token);
		}

		Throw new MissingTokenException(sprintf('No token available in current Session'));
	}
}