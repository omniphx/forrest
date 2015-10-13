<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Config\Repository as Config;
use Illuminate\Session\SessionManager as Session;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LaravelSessionSpec extends ObjectBehavior
{
    public function let(Config $config, Session $session)
    {
        $this->beConstructedWith($config, $session);
    }

    public function it_is_initializable(Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled();
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelSession');
    }

    public function it_should_allow_a_get(FakeSessionStore $session)
    {
        $session->has(Argument::any())->shouldBeCalled()->willReturn(true);
        $session->get(Argument::any())->shouldBeCalled();

        $this->get('test');
    }

    public function it_should_allow_a_put(FakeSessionStore $session)
    {
        $session->put(Argument::any(), Argument::any())->shouldBeCalled();

        $this->put('test', 'value');
    }

    public function it_should_allow_a_has(FakeSessionStore $session)
    {
        $session->has(Argument::any())->shouldBeCalled();

        $this->has('test');
    }
}

class FakeSessionStore extends Session
{
    public function has($str)
    {
    }

    public function get($str)
    {
    }

    public function put($str)
    {
    }
}
