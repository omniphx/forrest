<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use PhpSpec\ObjectBehavior;

class LaravelEventSpec extends ObjectBehavior
{
    public function let(Dispatcher $event)
    {
        $this->beConstructedWith($event);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelEvent');
    }
}
