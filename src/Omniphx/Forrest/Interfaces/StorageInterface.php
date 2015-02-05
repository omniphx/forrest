<?php namespace Omniphx\Forrest\Interfaces;

interface StorageInterface {
	
	/**
	 * Store into storage.
	 * @param $key
	 * @param $value
	 * @return void
	 */
	public function put($key, $value);

	/**
	 * Get from storage
	 * @param $key
	 * @return mixed
	 */
	public function get($key);

	/**
	 * Check if storage has a key stored
	 * @param $key
	 * @return boolean
	 */
	public function has($key);

	/**
	 * Encrypt authentication token and store it in storage.
	 * @param array $token
	 * @return void
	 */
	public function putToken($token);

	/**
	 * Get token from the storage and decrypt it.
	 * @return mixed
	 */
	public function getToken();

	/**
	 * Encrypt refresh token and pass into storage.
	 * @param  Array $token
	 * @return void
	 */
	public function putRefreshToken($token);

	/**
	 * Get refresh token from storage and decrypt it.
	 * @return mixed
	 */
	public function getRefreshToken();

	
}