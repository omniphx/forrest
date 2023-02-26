<?php

namespace Omniphx\Forrest\Providers\Laravel;

use GuzzleHttp\Client;
use Omniphx\Forrest\Providers\BaseServiceProvider;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;
use Omniphx\Forrest\Providers\Laravel\LaravelSession;
use Omniphx\Forrest\Providers\ObjectStorage;
use Omniphx\Forrest\Interfaces\StorageInterface;

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
        $client_config = app('config')->get('forrest.client', []);
        return new Client($client_config);
    }

    protected function getRedirect()
    {
        return new LaravelRedirect(app('redirect'));
    }

    protected function getStorage($storageType)
    {
        switch ($storageType) {
            case 'session':
                return new LaravelSession(app('config'), app('request')->session());
            case 'cache':
                return new LaravelCache(app('config'), app('cache')->store());
            case 'object':
                return new ObjectStorage();
            default:
                if(class_exists($storageType) && new $storageType() instanceof StorageInterface) {
                    return new $storageType();
                } else {
                    return new LaravelSession(app('config'), app('request')->session());
                }
        }
    }
}