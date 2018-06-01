<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Omniphx\Forrest\Interfaces\EventInterface;

class LaravelEvent implements EventInterface
{
    protected $event;

    public function __construct(Dispatcher $event)
    {
        $this->event = $event;
    }

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
        if (method_exists($this->event, 'dispatch')) {
            return $this->event->dispatch($event, $payload, $halt);
        }

        return $this->event->fire($event, $payload, $halt);
    }
}
