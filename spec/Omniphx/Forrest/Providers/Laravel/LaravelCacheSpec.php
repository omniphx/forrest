<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Illuminate\Config\Repository as Config;
use Illuminate\Cache\Repository as Cache;

class LaravelCacheSpec extends ObjectBehavior
{

    function let(Config $config, Cache $cache)
    {
        $this->beConstructedWith($config, $cache);
    }

    function it_is_initializable(Config $config, Cache $cache)
    {
        $config->get(Argument::any())->shouldBeCalled();
        $this->shouldHaveType('Omniphx\Forrest\Interfaces\StorageInterface');
    }

    function it_should_allow_a_get(Cache $cache)
    {
        $cache->has(Argument::any())->shouldBeCalled()->willReturn(true);
        $cache->get(Argument::any())->shouldBeCalled();

        $this->get('test');
    }

    function it_should_allow_a_put(Cache $cache)
    {
        $cache->put(Argument::any(), Argument::any(), Argument::type('integer'))->shouldBeCalled();

        $this->put('test', 'value');
    }

    function it_should_allow_a_has(Cache $cache)
    {
        $cache->has(Argument::any())->shouldBeCalled();

        $this->has('test');
    }

}
