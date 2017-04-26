<?php

namespace Omniphx\Forrest\Providers\Lumen;

use GuzzleHttp\Client;
use Omniphx\Forrest\Providers\BaseServiceProvider;
use Omniphx\Forrest\Providers\Lumen\LumenRedirect;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;

class ForrestServiceProvider extends BaseServiceProvider
{
    /**
     * Returns the location of the package config file.
     *
     * @return string file location
     */
    protected function getConfigPath()
    {
        return __DIR__.'/../config/forrest.php';
    }

    protected function getClient()
    {
        return new Client(['http_errors' => true]);
    }

    protected function getRedirect()
    {
        return new LumenRedirect(redirect());
    }

    protected function getStorage()
    {
        return new LumenCache(app('cache'), app('config'));
    }
}
