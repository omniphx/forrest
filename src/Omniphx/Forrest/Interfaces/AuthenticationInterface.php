<?php namespace Omniphx\Forrest\Interfaces;

interface AuthenticationInterface {

	/**
	 * Begin authentication process
	 * @return mixed
	 */
	public function authenticate();

	/**
	 * Send callback for Web Server flow.
	 * Should return null if flow doesn't need the callback function.
	 * @return array
	 */
	public function callback();

}