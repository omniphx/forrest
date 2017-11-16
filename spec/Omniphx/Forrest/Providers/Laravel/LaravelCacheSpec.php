<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LaravelCacheSpec extends ObjectBehavior
{
    public function let(Cache $cache, Config $config)
    {
        $this->beConstructedWith($config, $cache);
    }

    public function it_is_initializable(Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled();
        $this->shouldHaveType('Omniphx\Forrest\Interfaces\StorageInterface');
    }

    public function it_should_allow_a_get(Cache $cache, Config $config)
    {
        $cache->has(Argument::any())->shouldBeCalled()->willReturn(true);
        $cache->get(Argument::any())->shouldBeCalled()->willReturn('morty');

        $this->get('rick')->shouldReturn('morty');
    }

    public function it_should_allow_storing_cache_forever(Cache $cache, Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled()->willReturn(10);
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(true);
        $cache->forever(Argument::any(), Argument::any())->shouldBeCalled();

        $this->put('rick','morty');
    }

    public function it_should_allow_a_put(Cache $cache, Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled()->willReturn(10);
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(false);
        $cache->put(Argument::any(), Argument::any(), 10)->shouldBeCalled();

        $this->put('rick','morty');
    }

    public function it_should_not_allow_an_non_integer_to_be_set_for_expiration(Cache $cache, Config $config)
    {
        $config->get('forrest.storage.path')->shouldBeCalled()->willReturn('path');
        $config->get('forrest.storage.expire_in')->shouldBeCalled()->willReturn('asdfa');
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(false);
        $cache->put(Argument::any(), Argument::any(), 20)->shouldBeCalled();

        $this->put('rick','morty');
    }

    public function it_should_not_allow_an_negative_integer_to_be_set_for_expiration(Cache $cache, Config $config)
    {
        $config->get('forrest.storage.path')->shouldBeCalled()->willReturn('path');
        $config->get('forrest.storage.expire_in')->shouldBeCalled()->willReturn(-15);
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(false);
        $cache->put(Argument::any(), Argument::any(), 20)->shouldBeCalled();

        $this->put('rick','morty');
    }

    public function it_should_not_allow_string_integer_to_be_set_for_expiration(Cache $cache, Config $config)
    {
        $config->get('forrest.storage.path')->shouldBeCalled()->willReturn('path');
        $config->get('forrest.storage.expire_in')->shouldBeCalled()->willReturn('45');
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(false);
        $cache->put(Argument::any(), Argument::any(), 45)->shouldBeCalled();

        $this->put('rick','morty');
    }

    public function it_should_allow_a_has(Cache $cache)
    {
        $cache->has(Argument::any())->shouldBeCalled();

        $this->has('rick');
    }
}
