<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Contracts\Encryption\Encrypter;
use Omniphx\Forrest\Interfaces\EncryptorInterface;

class LaravelEncryptor implements EncryptorInterface
{
    protected $encryptor;

    public function __construct(Encrypter $encryptor)
    {
        $this->encryptor = $encryptor;
    }

    /**
     * Encrypt the given value.
     *
     * @param  string  $value
     * @return string
     */
    public function encrypt($value) {
        return $this->encryptor->encrypt($value);
    }

    /**
     * Decrypt the given value.
     *
     * @param  string  $payload
     * @return string
     */
    public function decrypt($payload) {
        return $this->encryptor->decrypt($payload);
    }
}
