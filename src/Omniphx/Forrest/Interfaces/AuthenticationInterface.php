<?php namespace Omniphx\Forrest\Interfaces;

interface AuthenticationInterface {

	/**
	 * Begin authentication process
	 * @param String $url
	 * @return mixed
	 */
	public function authenticate($url);

	/**
	 * Send callback for Web Server flow.
	 * Should return null if flow doesn't need the callback function.
	 * @return array
	 */
	public function callback();

    /**
     * Refresh authentication token
     * @param  Array $refreshToken
     * @return mixed $response
     */
	public function refresh($refreshToken);

}