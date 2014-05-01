<?php namespace Omniphx\Forrest;

use Omniphx\Forrest\Interfaces\RedirectInterface;
use Redirect;

class LaravelRedirect implements RedirectInterface {

	public function to($parameter){
		return Redirect::to($parameter);
	}
}