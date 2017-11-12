<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Contracts\Events\Dispatcher as Event;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LaravelEventSpec extends ObjectBehavior
{
    public function let(Event $event)
    {
        $this->beConstructedWith($event);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelEvent');
    }

    public function it_should_fire_an_event(Event $event)
    {
        $event->dispatch(Argument::type('string'), Argument::type('array'), Argument::type('bool'))->shouldBeCalled();
        $this->fire('event',[]);
    }
}
