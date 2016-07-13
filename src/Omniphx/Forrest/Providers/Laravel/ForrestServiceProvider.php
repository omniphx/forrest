<?php

namespace Omniphx\Forrest\Providers\Laravel;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;

class ForrestServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../../../config/config.php' => config_path('forrest.php'),
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

            //Config options:
            $settings = config('forrest');
            $storageType = config('forrest.storage.type');
            $authenticationType = config('forrest.authentication');

            //Dependencies:
            $client = new Client(['http_errors' => false]);
            $input = new LaravelInput();
            $event = new LaravelEvent();
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
}
