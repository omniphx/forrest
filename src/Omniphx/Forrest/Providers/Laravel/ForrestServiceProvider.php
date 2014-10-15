<?php namespace Omniphx\Forrest\Providers\Laravel;

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
		$this->package('omniphx/forrest', null, __DIR__.'/../../../..');

		$authentication  = Config::get('forrest::authentication');

		include __DIR__ . "/routes/$authentication.php";
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
			$settings  = Config::get('forrest::config');

			$client   = new \GuzzleHttp\Client();
			$redirect = new \Omniphx\Forrest\Providers\Laravel\LaravelRedirect();
			$session  = new \Omniphx\Forrest\Providers\Laravel\LaravelSession();
			$input    = new \Omniphx\Forrest\Providers\Laravel\LaravelInput();

			$authentication = '\\Omniphx\\Forrest\\Authentications\\';
			$authentication .= $settings['authentication'];

			return new $authentication($client, $session, $redirect, $input, $settings);
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