<?php

namespace Omniphx\Forrest\Providers\Lumen;

use Omniphx\Forrest\Providers\BaseServiceProvider;

class ForrestServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if the application is laravel/lumen.
     *
     * @var bool
     */
    protected $is_laravel = false;

    /**
     * Returns the location of the package config file.
     *
     * @return string file location
     */
    protected function getConfigPath()
    {
        return __DIR__.'/../config/forrest.php';
    }
}
