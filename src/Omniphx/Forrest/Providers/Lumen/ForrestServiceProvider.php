<?php

namespace Omniphx\Forrest\Providers\Lumen;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Omniphx\Forrest\Providers\Laravel\LaravelEvent;
use Omniphx\Forrest\Providers\Laravel\LaravelInput;
use Omniphx\Forrest\Providers\Laravel\LaravelRedirect;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;
use Omniphx\Forrest\Providers\Laravel\LaravelSession;

class ForrestServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__.'/../../../../config/config.php' => $this->configPath(),
        ]);

        $this->checkForAuthentication();
    }

    protected function checkForAuthentication()
    {
        $authentication = config('forrest.authentication');
        if (!empty($authentication)) {
            require_once __DIR__ . "/Routes/{$authentication}.php";
        }
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('forrest', function ($app) {

            //Config options:
            $settings           = config('forrest');
            $storageType        = config('forrest.storage.type');
            $authenticationType = config('forrest.authentication');

            //Dependencies:
            $client   = new Client();
            $input    = new LaravelInput();
            $event    = new LaravelEvent();
            $redirect = new LaravelRedirect();

            //Determine storage dependency:
            if ($storageType == 'cache') {
                $storage = new LaravelCache(app('config'), app('cache'));
            } else {
                $storage = new LaravelSession(app('config'), app('session'));
            }

            //Class namespace:
            $forrest = "\\Omniphx\\Forrest\\Authentications\\$authenticationType";

            return new $forrest($client, $event, $input, $redirect, $storage, $settings);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    protected function configPath()
    {
        return __DIR__.'/../config/forrest.php';
    }
}
