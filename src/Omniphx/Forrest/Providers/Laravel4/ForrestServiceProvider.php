<?php

namespace Omniphx\Forrest\Providers\Laravel4;

use Config;
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
        $this->package('omniphx/forrest', null, __DIR__.'/../../../..');

        $authentication = Config::get('forrest::authentication');

        include __DIR__."/../Laravel4/Routes/$authentication.php";
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['forrest'] = $this->app->share(function ($app) {
            $settings = Config::get('forrest::config');

            $client = new \GuzzleHttp\Client();
            $redirect = new \Omniphx\Forrest\Providers\Laravel4\LaravelRedirect();
            if ($settings['storage']['type'] == 'cache') {
                $storage = new \Omniphx\Forrest\Providers\Laravel4\LaravelCache(app('config'), app('cache'));
            } else {
                $storage = new \Omniphx\Forrest\Providers\Laravel4\LaravelSession(app('config'), app('session'));
            }
            $input = new \Omniphx\Forrest\Providers\Laravel4\LaravelInput();
            $event = new \Omniphx\Forrest\Providers\Laravel4\LaravelEvent();

            $forrest = '\\Omniphx\\Forrest\\Authentications\\';
            $forrest .= $settings['authentication'];

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
