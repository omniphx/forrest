<?php namespace Omniphx\Forrest\Interfaces;

interface SessionInterface {
	
	/**
	 * Store into session.
	 * @param $key
	 * @param $value
	 * @return void
	 */
	public function put($key, $value);

	/**
	 * Get from session
	 * @param $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * Encrypt authentication token and store it in session.
	 * @param array $token
	 * @return void
	 */
	public function putToken($token);

	/**
	 * Get token from the session and decrypt it.
	 * @return mixed
	 */
	public function getToken();

	/**
	 * Encrypt refresh token and pass into session.
	 * @param  Array $token
	 * @return void
	 */
	public function putRefreshToken($token);

	/**
	 * Get refresh token from session and decrypt it.
	 * @return mixed
	 */
	public function getRefreshToken();

	
}