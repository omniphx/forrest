<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Event;
use Omniphx\Forrest\Interfaces\EventInterface;

class LaravelEvent implements EventInterface
{
    /**
     * Fire an event and call the listeners.
     *
     * @param string $event
     * @param mixed  $payload
     * @param bool   $halt
     *
     * @return array|null
     */
    public function fire($event, $payload = [], $halt = false)
    {
        return Event::fire($event, $payload, $halt);
    }
}
