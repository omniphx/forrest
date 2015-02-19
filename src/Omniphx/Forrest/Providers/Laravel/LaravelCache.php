<?php namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Illuminate\Config\Repository as Config;
use Illuminate\Cache\CacheManager as Cache;

class LaravelCache extends LaravelStorageProvider implements StorageInterface {

	public $minutes = 20;

	public $path;

	protected $cache;

	public function __construct(Config $config, Cache $cache)
	{
		$this->path = $config->get('forrest::config.storage.path');

		$this->cache = $cache;

		$this->minutes = $config->get('forrest::config.storage.expire_in');
	}

	/**
	 * Store into session.
	 * @param $key
	 * @param $value
	 * @return void
	 */
	public function put($key, $value)
	{
		return $this->cache->put($this->path.$key, $value, $this->minutes);
	}

	/**
	 * Get from session
	 * @param $key
	 * @return mixed
	 */
	public function get($key)
	{
		if ($this->cache->has($this->path.$key)) {
			return $this->cache->get($this->path.$key);
		}

		throw new MissingKeyException(sprintf("No value for requested key: %s",$key));
	}

	/**
	 * Check if storage has a key
	 * @param $key
	 * @return boolean
	 */
	public function has($key)
	{
		return $this->cache->has($this->path.$key);
	}

}
