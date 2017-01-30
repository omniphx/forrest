<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Config\Repository as Config;
use Illuminate\Http\Request;
use Omniphx\Forrest\Exceptions\MissingKeyException;

class LaravelSession extends LaravelStorageProvider
{
    public $path;

    protected $request;

    public function __construct(Config $config, Request $request)
    {
        $this->path = $config->get('forrest.storage.path');
        $this->request = $request;
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
        return $this->request->session()->put($this->path.$key, $value);
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
        if ($this->has($key)) {
            return $this->request->session()->get($this->path.$key);
        }

        throw new MissingKeyException(sprintf('No value for requested key: %s', $key));
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
        return $this->request->session()->has($this->path.$key);
    }
}
