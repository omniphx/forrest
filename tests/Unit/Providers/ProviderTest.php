<?php

namespace Tests\Unit\Providers;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Omniphx\Forrest\Exceptions\MissingKeyException;
use Omniphx\Forrest\Providers\Laravel\LaravelCache;
use Omniphx\Forrest\Providers\Laravel\LaravelEvent;
use Omniphx\Forrest\Providers\Laravel\LaravelInput;
use Omniphx\Forrest\Providers\Laravel\LaravelRedirect;
use Omniphx\Forrest\Providers\Laravel\LaravelSession;
use Omniphx\Forrest\Providers\Lumen\LumenRedirect;
use Tests\TestCase;

class ProviderTest extends TestCase
{
    public function testLaravelCacheUsesConfiguredExpiration(): void
    {
        $config = $this->configMock('forrest_', false, 20);
        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())->method('put')->with('forrest_token', 'value', 20);

        (new LaravelCache($config, $cache))->put('token', 'value');
    }

    public function testLaravelCacheFallsBackToDefaultExpirationForInvalidValues(): void
    {
        $config = $this->configMock('forrest_', false, 'invalid');
        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())->method('put')->with('forrest_token', 'value', 600);

        (new LaravelCache($config, $cache))->put('token', 'value');
    }

    public function testLaravelCacheCanStoreForever(): void
    {
        $config = $this->configMock('forrest_', true, 20);
        $cache = $this->createMock(CacheRepository::class);
        $cache->expects($this->once())->method('forever')->with('forrest_token', 'value');

        (new LaravelCache($config, $cache))->put('token', 'value');
    }

    public function testLaravelCacheThrowsWhenKeyIsMissing(): void
    {
        $config = $this->configMock('forrest_', false, 20);
        $cache = $this->createMock(CacheRepository::class);
        $cache->method('has')->with('forrest_token')->willReturn(false);

        $this->expectException(MissingKeyException::class);

        (new LaravelCache($config, $cache))->get('token');
    }

    public function testLaravelSessionPrefixesStoredKeys(): void
    {
        $config = $this->createStub(\Illuminate\Config\Repository::class);
        $config->method('get')->willReturn('forrest_');
        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('put')->with('forrest_token', 'value');

        (new LaravelSession($config, $session))->put('token', 'value');
    }

    public function testLaravelSessionThrowsWhenKeyIsMissing(): void
    {
        $config = $this->createStub(\Illuminate\Config\Repository::class);
        $config->method('get')->willReturn('forrest_');
        $session = $this->createMock(Session::class);
        $session->method('has')->with('forrest_token')->willReturn(false);

        $this->expectException(MissingKeyException::class);

        (new LaravelSession($config, $session))->get('token');
    }

    public function testLaravelEventDispatchesPayload(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->expects($this->once())->method('dispatch')->with('forrest.response', ['payload'], false)->willReturn(['ok']);

        $this->assertSame(['ok'], (new LaravelEvent($dispatcher))->fire('forrest.response', ['payload']));
    }

    public function testLaravelInputDelegatesToRequestInput(): void
    {
        $request = Request::create('/callback', 'GET', ['code' => 'abc']);

        $this->assertSame('abc', (new LaravelInput($request))->get('code'));
    }

    public function testRedirectProvidersDelegateToRedirector(): void
    {
        $redirector = $this->createMock(Redirector::class);
        $redirector->expects($this->once())->method('to')->with('https://example.test')->willReturn('redirected');
        $this->assertSame('redirected', (new LaravelRedirect($redirector))->to('https://example.test'));
    }

    public function testLumenRedirectDelegatesWhenLumenRedirectorIsAvailable(): void
    {
        if (! class_exists(\Laravel\Lumen\Http\Redirector::class)) {
            $this->markTestSkipped('Lumen is not installed in this test environment.');
        }

        $lumenRedirector = $this->createMock(\Laravel\Lumen\Http\Redirector::class);
        $lumenRedirector->expects($this->once())->method('to')->with('https://example.test')->willReturn('redirected');

        $this->assertSame('redirected', (new LumenRedirect($lumenRedirector))->to('https://example.test'));
    }

    private function configMock(string $path, bool $storeForever, $expiration): ConfigContract
    {
        $config = $this->createStub(ConfigContract::class);
        $config->method('get')->willReturnMap([
            ['forrest.storage.path', null, $path],
            ['forrest.storage.store_forever', null, $storeForever],
            ['forrest.storage.expire_in', null, $expiration],
        ]);

        return $config;
    }
}
