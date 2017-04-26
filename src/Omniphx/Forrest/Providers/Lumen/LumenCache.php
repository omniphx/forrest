<?php

namespace Omniphx\Forrest\Providers\Lumen;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Omniphx\Forrest\Providers\Laravel\LaravelStorageProvider as StorageProvider;

class LumenCache extends StorageProvider
{
    protected $cache;
    protected $path;
    protected $minutes = 20;
    protected $storeForever;

    public function __construct(Cache $cache, Config $config)
    {
        $this->cache            = $cache;
        $this->path             = $config->get('forrest.storage.path');
        $this->storeForever     = $config->get('forrest.storage.store_forever');
        $this->expirationConfig = $config->get('forrest.storage.expire_in');
        $this->setMinutes();
    }

    /**
     * Store into session.
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        if ($this->storeForever) {
            return $this->cache->forever($this->path.$key, $value);
        } else {
            return $this->cache->put($this->path.$key, $value, $this->minutes);
        }
    }

    /**
     * Get from session.
     *
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->checkForKey($key);

        return $this->cache->get($this->path.$key);
    }

    /**
     * Check if storage has a key.
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->cache->has($this->path.$key);
    }

    /**
     * @return void
     */
    protected function setMinutes() {
        if(!$this->checkIfPositiveInteger($this->expirationConfig)) return;
        $this->minutes = $this->expirationConfig;
    }

    /**
     * @return mixed
     */
    protected function checkForKey($key) {
        if($this->cache->has($this->path.$key)) return;

        throw new MissingKeyException(sprintf('No value for requested key: %s', $key));
    }

    protected function checkIfPositiveInteger($integer) {
        return $this->checkIfInteger($integer) && $this->checkIfPositive($integer);
    }

    protected function checkIfInteger($integer) {
        return filter_var($integer, FILTER_VALIDATE_INT) !== false;
    }

    protected function checkIfPositive($integer) {
        return $integer > 0;
    }
}
