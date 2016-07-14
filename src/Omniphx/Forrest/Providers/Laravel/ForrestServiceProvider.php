<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Providers\BaseServiceProvider;

class ForrestServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if the application is laravel/lumen.
     *
     * @var bool
     */
    protected $is_laravel = true;

    /**
     * Returns the location of the package config file.
     *
     * @return string file location
     */
    protected function getConfigPath()
    {
        return config_path('forrest.php');
    }
}
