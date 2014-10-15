<?php namespace Omniphx\Forrest\Interfaces;

interface AuthenticationInterface {

	/**
	 * Begin authentication process
	 * @param String $url
	 * @return mixed
	 */
	public function authenticate($url);

}