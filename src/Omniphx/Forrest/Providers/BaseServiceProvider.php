<?php

namespace Omniphx\Forrest\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;
use Omniphx\Forrest\Providers\Laravel\LaravelEvent;
use Omniphx\Forrest\Providers\Laravel\LaravelInput;
use Omniphx\Forrest\Providers\Laravel\LaravelRedirect;
use Omniphx\Forrest\Providers\Laravel\LaravelSession;

abstract class BaseServiceProvider extends ServiceProvider
{
    /**
     * Indicates if the application is laravel/lumen.
     *
     * @var bool
     */
    protected $is_laravel = true;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Returns the location of the package config file.
     *
     * @return string file location
     */
    abstract protected function getConfigPath();

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (method_exists($this, 'getConfigPath')) {
            $this->publishes([
                __DIR__.'/../../../config/config.php' => $this->getConfigPath(),
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('forrest', function ($app) {

            // Config options
            $settings = config('forrest');
            $storageType = config('forrest.storage.type');
            $authenticationType = config('forrest.authentication');

            // Determine showing HTTP errors
            $http_errors = $this->is_laravel ? true : false;

            // Dependencies
            $client = new Client(['http_errors' => $http_errors]);
            $input = new LaravelInput();
            $event = new LaravelEvent();
            $redirect = new LaravelRedirect();

            // Determine storage dependency
            if ($storageType == 'cache') {
                $storage = new LaravelCache(app('config'), app('cache'));
            } else {
                $storage = new LaravelSession(app('config'), app('session'));
            }

            // Class namespace
            $forrest = "\\Omniphx\\Forrest\\Authentications\\$authenticationType";

            return new $forrest($client, $event, $input, $redirect, $storage, $settings);
        });
    }
}
