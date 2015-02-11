<?php namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Support\ServiceProvider;

class ForrestServiceProvider extends ServiceProvider {

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

		$authentication = config('forrest.authentication');

		if(!is_null($authentication)){
			include __DIR__ . "/Routes/$authentication.php";
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bindShared('forrest', function($app){

			//Config options:
			$settings           = config('forrest');
			$authenticationType = config('forrest.authentication');
			$storageType        = config('forrest.storage.type');

			//Dependencies:
			$client   = new \GuzzleHttp\Client();
			$input    = new \Omniphx\Forrest\Providers\Laravel\LaravelInput();
			$event    = new \Omniphx\Forrest\Providers\Laravel\LaravelEvent();
			$redirect = new \Omniphx\Forrest\Providers\Laravel\LaravelRedirect();

			//Determine storage dependency:
			if($storageType == 'cache') {
				$storage  = new \Omniphx\Forrest\Providers\Laravel\LaravelCache(app('config'), app('cache'));
			} else {
				$storage  = new \Omniphx\Forrest\Providers\Laravel\LaravelSession(app('config'), app('session'));
			}

			//Class namespace:
			$forrest = "\\Omniphx\\Forrest\\Authentications\\$authenticationType";

			return new $forrest($client, $storage, $redirect, $input, $event, $settings);

		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}