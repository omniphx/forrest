<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Cache\CacheManager as Cache;
use Illuminate\Config\Repository as Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LaravelCacheSpec extends ObjectBehavior
{
    public function let(Config $config, Cache $cache)
    {
        $this->beConstructedWith($config, $cache);
    }

    public function it_is_initializable(Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled();
        $this->shouldHaveType('Omniphx\Forrest\Interfaces\StorageInterface');
    }

    public function it_should_allow_a_get(FakeCacheStore $cache)
    {
        $cache->has(Argument::any())->shouldBeCalled()->willReturn(true);
        $cache->get(Argument::any())->shouldBeCalled();

        $this->get('test');
    }

    public function it_should_allow_storing_cache_forever(FakeCacheStore $cache, Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled()->willReturn(10);
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(true);
        $cache->forever(Argument::any(), Argument::any())->shouldBeCalled();

        $this->put('test', 'value');
    }

    public function it_should_allow_a_put(FakeCacheStore $cache, Config $config)
    {
        $config->get(Argument::any())->shouldBeCalled()->willReturn(10);
        $config->get('forrest.storage.store_forever')->shouldBeCalled()->willReturn(false);
        $cache->put(Argument::any(), Argument::any(), Argument::type('integer'))->shouldBeCalled();

        $this->put('test', 'value');
    }

    public function it_should_allow_a_has(FakeCacheStore $cache)
    {
        $cache->has(Argument::any())->shouldBeCalled();

        $this->has('test');
    }
}

class FakeCacheStore extends Cache
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

    public function forever($str)
    {
    }
}
