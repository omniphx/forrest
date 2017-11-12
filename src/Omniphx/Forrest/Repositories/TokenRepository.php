<?php

namespace Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingTokenException;

class TokenRepository implements RepositoryInterface {

    protected $encryptor;
    protected $storage;

    public function __construct(EncryptorInterface $encryptor, StorageInterface $storage) {
        $this->encryptor = $encryptor;
        $this->storage   = $storage;
    }

    /**
     * Encrypt authentication token and store it in session/cache.
     *
     * @param array $token
     *
     * @return void
     */
    public function put($token)
    {
        $encryptedToken = $this->encryptor->encrypt($token);

        $this->storage->put('token', $encryptedToken);
    }

    /**
     * Get refresh token from session and decrypt it.
     *
     * @return mixed
     */
    public function get()
    {
        $this->verify();

        $token = $this->storage->get('token');

        return $this->encryptor->decrypt($token);
    }

    public function has() {
        return $this->storage->has('token');
    }

    private function verify() {
        if ($this->storage->has('token')) return;

        throw new MissingTokenException('No token available');
    }
}