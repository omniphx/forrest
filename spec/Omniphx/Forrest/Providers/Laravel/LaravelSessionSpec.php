<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Illuminate\Config\Repository as Config;
use Illuminate\Session\SessionManager as Session;

class LaravelSessionSpec extends ObjectBehavior
{

    function let(Config $config, Session $session)
    {
        $this->beConstructedWith($config, $session);
    }

    function it_is_initializable(Config $config, Session $session)
    {
        $config->get(Argument::any())->shouldBeCalled();
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelSession');
    }

    function it_should_allow_a_get(FakeSessionStore $session)
    {
        $session->has(Argument::any())->shouldBeCalled()->willReturn(true);
        $session->get(Argument::any())->shouldBeCalled();

        $this->get('test');
    }

    function it_should_allow_a_put(FakeSessionStore $session)
    {
        $session->put(Argument::any(), Argument::any())->shouldBeCalled();

        $this->put('test', 'value');
    }

    function it_should_allow_a_has(FakeSessionStore $session)
    {
        $session->has(Argument::any())->shouldBeCalled();

        $this->has('test');
    }

}

class FakeSessionStore extends Session {
    function has($str) {}
    function get($str) {}
    function put($str) {}
}