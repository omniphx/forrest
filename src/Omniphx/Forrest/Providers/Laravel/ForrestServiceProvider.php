<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Providers\BaseServiceProvider;
use GuzzleHttp\Client;

class ForrestServiceProvider extends BaseServiceProvider
{
    /**
     * Returns the location of the package config file.
     *
     * @return string file location
     */
    protected function getConfigPath()
    {
        return config_path('forrest.php');
    }

    protected function getClient()
    {
        return new Client(['http_errors' => true]);
    }
}
