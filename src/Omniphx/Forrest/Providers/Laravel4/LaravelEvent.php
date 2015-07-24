<?php namespace Omniphx\Forrest\Providers\Laravel4;

use Omniphx\Forrest\Interfaces\EventInterface;
use Event;

class LaravelEvent implements EventInterface {

	/**
	 * Fire an event and call the listeners.
	 *
	 * @param  string  $event
	 * @param  mixed   $payload
	 * @param  bool    $halt
	 * @return array|null
	 */
	public function fire($event, $payload = array(), $halt = false) {
		return Event::fire($event, $payload, $halt);
	}
}