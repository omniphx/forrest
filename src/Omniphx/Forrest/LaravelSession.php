<?php namespace Omniphx\Forrest;

use Omniphx\Forrest\Interfaces\SessionInterface;
use Session;

class LaravelSession implements SessionInterface {

	public function get($key){
		return Session::get($key);
	}

	public function put($key, $value){
		return Session::put($key, $value);
	}
}