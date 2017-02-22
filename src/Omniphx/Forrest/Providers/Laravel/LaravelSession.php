<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Session\Session as Session;
use Omniphx\Forrest\Exceptions\MissingKeyException;

class LaravelSession extends LaravelStorageProvider
{
    public $path;

    protected $session;

    public function __construct(Config $config, Session $session)
    {
        $this->path = $config->get('forrest.storage.path');
        $this->session = $session;
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
        return $this->session->put($this->path.$key, $value);
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
        if(!$this->has($key)) {
            throw new MissingKeyException(sprintf('No value for requested key: %s', $key));
        }

        return $this->session->get($this->path.$key);
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
        return $this->session->has($this->path.$key);
    }
}
