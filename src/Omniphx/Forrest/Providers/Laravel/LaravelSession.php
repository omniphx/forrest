<?php namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingTokenException;
use Omniphx\Forrest\Exceptions\MissingRefreshTokenException;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Session;
use Crypt;

class LaravelSession implements StorageInterface {

	/**
	 * Store into session.
	 * @param $key
	 * @param $value
	 * @return void
	 */
	public function put($key, $value)
	{
		return Session::put($key, $value);
	}

	/**
	 * Get from session
	 * @param $key
	 * @return mixed
	 */
	public function get($key)
	{
		$value = Session::get($key);
		if (isset($value)) {
			return Session::get($key);
		}

		throw new MissingKeyException(sprintf("No value for requested key: %s",$key));
	}

	/**
	 * Encrypt authentication token and store it in session.
	 * @param array $token
	 * @return void
	 */
	public function putToken($token)
	{
		$encryptedToken = Crypt::encrypt($token);
		return Session::put('token', $encryptedToken);
	}

	/**
	 * Get token from the session and decrypt it.
	 * @return mixed
	 */
	public function getToken(){
		$token = Session::get('token');
		if (isset($token)) {
			return Crypt::decrypt($token);
		}

		throw new MissingTokenException(sprintf('No token available in current session'));
	}

	/**
	 * Encrypt refresh token and pass into session.
	 * @param  Array $token
	 * @return void
	 */
	public function putRefreshToken($token)
	{
		$encryptedToken = Crypt::encrypt($token);
		return Session::put('refresh_token', $encryptedToken);
	}

	/**
	 * Get refresh token from session and decrypt it.
	 * @return mixed
	 */
	public function getRefreshToken()
	{
		$token = Session::get('refresh_token');
		if (isset($token)) {
			return Crypt::decrypt($token);
		}

		throw new MissingRefreshTokenException(sprintf('No refresh token stored in current session. Verify you have added refresh_token to your scope items on your connected app settings in Salesforce.'));
	}
}