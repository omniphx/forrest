<?php namespace Omniphx\Forrest;

use Config;
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
		$this->package('omniphx/forrest');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{	

		$this->app['forrest'] = $this->app->share(function($app)
		{
			$client   = new \GuzzleHttp\Client();
			$redirect = new \Omniphx\Forrest\LaravelRedirect();
			$session  = new \Omniphx\Forrest\LaravelSession();
			$input    = new \Omniphx\Forrest\LaravelInput();
			$settings  = Config::get('forrest::settings');

			return new RestAPI($client, $session, $redirect, $input, $settings);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(forrest);
	}

}