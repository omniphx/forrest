<?php namespace Omniphx\Forrest\Interfaces;

interface SessionInterface {

	public function get($key);

	public function put($key, $value);
	
}