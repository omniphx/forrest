<?php

namespace spec\Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Repositories\RefreshTokenRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingRefreshTokenException;

class RefreshTokenRepositorySpec extends ObjectBehavior
{
    public function let(EncryptorInterface $mockedEncryptor, StorageInterface $mockedStorage) {
        $this->beConstructedWith($mockedEncryptor, $mockedStorage);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(RefreshTokenRepository::class);
    }

    function it_should_store_refresh_token($mockedEncryptor, $mockedStorage) {
        $mockedEncryptor->encrypt('token')->willReturn('encryptedToken');
        $mockedStorage->put('refresh_token', 'encryptedToken')->shouldBeCalled();

        $this->put('token');
    }

    function it_should_retrieve_refresh_token($mockedEncryptor, $mockedStorage) {
        $mockedStorage->has('refresh_token')->willReturn(true);
        $mockedStorage->get('refresh_token')->willReturn('encryptedToken');
        $mockedEncryptor->decrypt('encryptedToken')->willReturn('decryptedToken');

        $this->get()->shouldReturn('decryptedToken');
    }

    function it_should_throw_an_error_if_storage_does_not_have_refresh_token($mockedEncryptor, $mockedStorage) {
        $mockedStorage->has('refresh_token')->willReturn(false);

        $missingTokenException = new MissingRefreshTokenException('No refresh token stored in current session. Verify you have added refresh_token to your scope items on your connected app settings in Salesforce.');

        $this->shouldThrow($missingTokenException)->duringGet();
    }
}
