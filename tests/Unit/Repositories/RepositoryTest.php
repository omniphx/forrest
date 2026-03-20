<?php

namespace Tests\Unit\Repositories;

use Omniphx\Forrest\Exceptions\MissingRefreshTokenException;
use Omniphx\Forrest\Exceptions\MissingResourceException;
use Omniphx\Forrest\Exceptions\MissingStateException;
use Omniphx\Forrest\Exceptions\MissingTokenException;
use Omniphx\Forrest\Exceptions\MissingVersionException;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Repositories\InstanceURLRepository;
use Omniphx\Forrest\Repositories\RefreshTokenRepository;
use Omniphx\Forrest\Repositories\ResourceRepository;
use Omniphx\Forrest\Repositories\StateRepository;
use Omniphx\Forrest\Repositories\TokenRepository;
use Omniphx\Forrest\Repositories\VersionRepository;
use Tests\TestCase;

class RepositoryTest extends TestCase
{
    public function testTokenRepositoryEncryptsAndStoresValues(): void
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $storage = $this->createMock(StorageInterface::class);

        $encryptor->expects($this->once())->method('encrypt')->with(['access_token' => 'abc'])->willReturn('encrypted');
        $storage->expects($this->once())->method('put')->with('token', 'encrypted');

        (new TokenRepository($encryptor, $storage))->put(['access_token' => 'abc']);
    }

    public function testTokenRepositoryDecryptsStoredValues(): void
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $storage = $this->createMock(StorageInterface::class);

        $storage->method('has')->with('token')->willReturn(true);
        $storage->method('get')->with('token')->willReturn('encrypted');
        $encryptor->method('decrypt')->with('encrypted')->willReturn(['access_token' => 'abc']);

        $this->assertSame(['access_token' => 'abc'], (new TokenRepository($encryptor, $storage))->get());
    }

    public function testTokenRepositoryThrowsWhenTokenIsMissing(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('token')->willReturn(false);

        $this->expectException(MissingTokenException::class);

        (new TokenRepository($this->createStub(EncryptorInterface::class), $storage))->get();
    }

    public function testRefreshTokenRepositoryEncryptsAndRetrievesValues(): void
    {
        $encryptor = $this->createMock(EncryptorInterface::class);
        $storage = $this->createMock(StorageInterface::class);

        $encryptor->expects($this->once())->method('encrypt')->with('refresh')->willReturn('enc');
        $storage->expects($this->once())->method('put')->with('refresh_token', 'enc');

        $repository = new RefreshTokenRepository($encryptor, $storage);
        $repository->put('refresh');

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('refresh_token')->willReturn(true);
        $storage->method('get')->with('refresh_token')->willReturn('enc');
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('decrypt')->with('enc')->willReturn('refresh');

        $this->assertSame('refresh', (new RefreshTokenRepository($encryptor, $storage))->get());
    }

    public function testRefreshTokenRepositoryThrowsWhenMissing(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('refresh_token')->willReturn(false);

        $this->expectException(MissingRefreshTokenException::class);

        (new RefreshTokenRepository($this->createStub(EncryptorInterface::class), $storage))->get();
    }

    public function testResourceRepositoryStoresAndReadsNamedResources(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('put')->with('resources', ['query' => '/services/data/v1/query']);

        (new ResourceRepository($storage))->put(['query' => '/services/data/v1/query']);
    }

    public function testResourceRepositoryReturnsResourceValues(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('resources')->willReturn(true);
        $storage->method('get')->with('resources')->willReturn(['query' => '/services/data/v1/query']);

        $this->assertSame('/services/data/v1/query', (new ResourceRepository($storage))->get('query'));
    }

    public function testResourceRepositoryThrowsWhenMissing(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('resources')->willReturn(false);

        $this->expectException(MissingResourceException::class);

        (new ResourceRepository($storage))->get('query');
    }

    public function testStateRepositoryStoresAndReturnsState(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('put')->with('stateOptions', ['loginUrl' => 'https://login.salesforce.com']);

        (new StateRepository($storage))->put(['loginUrl' => 'https://login.salesforce.com']);

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('stateOptions')->willReturn(true);
        $storage->method('get')->with('stateOptions')->willReturn(['loginUrl' => 'https://login.salesforce.com']);

        $this->assertSame(['loginUrl' => 'https://login.salesforce.com'], (new StateRepository($storage))->get());
    }

    public function testStateRepositoryThrowsWhenMissing(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('stateOptions')->willReturn(false);

        $this->expectException(MissingStateException::class);

        (new StateRepository($storage))->get();
    }

    public function testVersionRepositoryStoresAndReturnsVersion(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('put')->with('version', ['version' => '59.0']);

        (new VersionRepository($storage))->put(['version' => '59.0']);

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('version')->willReturn(true);
        $storage->method('get')->with('version')->willReturn(['version' => '59.0']);

        $this->assertSame(['version' => '59.0'], (new VersionRepository($storage))->get());
    }

    public function testVersionRepositoryThrowsWhenMissing(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('has')->with('version')->willReturn(false);

        $this->expectException(MissingVersionException::class);

        (new VersionRepository($storage))->get();
    }

    public function testInstanceUrlRepositoryCanOverrideStoredTokenValue(): void
    {
        $tokenRepo = $this->createStub(RepositoryInterface::class);
        $tokenRepo->method('get')->willReturn(['instance_url' => 'https://na1.salesforce.com']);

        $repository = new InstanceURLRepository($tokenRepo, ['instanceURL' => 'https://override.salesforce.com']);

        $this->assertSame('https://override.salesforce.com', $repository->get());
    }

    public function testInstanceUrlRepositoryUpdatesTokenWhenPuttingValue(): void
    {
        $tokenRepo = $this->createMock(RepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('get')->willReturn(['instance_url' => 'https://na1.salesforce.com', 'access_token' => 'abc']);
        $tokenRepo->expects($this->once())->method('put')->with(['instance_url' => 'https://override.salesforce.com', 'access_token' => 'abc']);

        (new InstanceURLRepository($tokenRepo, []))->put('https://override.salesforce.com');
    }
}
