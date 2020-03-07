<?php

namespace Omniphx\Forrest\Providers;

use Omniphx\Forrest\Exceptions\MissingKeyException;
use Omniphx\Forrest\Exceptions\LaravelStorageProvider;
use Omniphx\Forrest\Interfaces\StorageInterface;

class ObjectStorage implements StorageInterface
{
    protected $store = [];

    /**
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        $this->store[$key] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if(!$this->has($key)) {
            throw new MissingKeyException(sprintf('No value for requested key: %s', $key));
        }

        return $this->store[$key];
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->store);
    }
}
