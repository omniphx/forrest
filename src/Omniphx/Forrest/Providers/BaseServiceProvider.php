<?php

namespace Omniphx\Forrest\Providers;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Omniphx\Forrest\Authentications\WebServer;
use Omniphx\Forrest\Authentications\UserPassword;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;
use Omniphx\Forrest\Providers\Laravel\LaravelEvent;
use Omniphx\Forrest\Providers\Laravel\LaravelEncryptor;
use Omniphx\Forrest\Providers\Laravel\LaravelInput;
use Omniphx\Forrest\Providers\Laravel\LaravelRedirect;
use Omniphx\Forrest\Providers\Laravel\LaravelSession;

abstract class BaseServiceProvider extends ServiceProvider
{
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
     * Returns client implementation
     *
     * @return GuzzleHttp\Client
     */
    protected abstract function getClient();

    /**
     * Returns client implementation
     *
     * @return GuzzleHttp\Client
     */
    protected abstract function getRedirect();

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        if (!method_exists($this, 'getConfigPath')) return;

        $this->publishes([
            __DIR__.'/../../../config/config.php' => $this->getConfigPath(),
        ]);
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
            $settings           = config('forrest');
            $storageType        = config('forrest.storage.type');
            $authenticationType = config('forrest.authentication');

            // Dependencies
            $client    = $this->getClient();
            $input     = new LaravelInput(app('request'));
            $event     = new LaravelEvent(app('events'));
            $encryptor = new LaravelEncryptor(app('encrypter'));
            $redirect  = $this->getRedirect();
            $storage   = $this->getStorage();

            switch ($authenticationType) {
                case 'WebServer':
                    $forrest = new WebServer(
                        $client,
                        $encryptor,
                        $event,
                        $input,
                        $redirect,
                        $storage,
                        $settings);
                    break;
                case 'UserPassword':
                    $forrest = new UserPassword(
                        $client,
                        $encryptor,
                        $event,
                        $input,
                        $redirect,
                        $storage,
                        $settings);
                    break;
                default:
                    $forrest = new WebServer(
                        $client,
                        $encryptor,
                        $event,
                        $input,
                        $redirect,
                        $storage,
                        $settings);
                    break;
            }

            return $forrest;
        });
    }
}
