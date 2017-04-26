<?php

namespace Omniphx\Forrest\Interfaces;

interface EncryptorInterface
{
    /**
     * Encrypt
     *
     * @return mixed
     */
    public function encrypt($token);

    /**
     * Decrypt
     *
     * @return mixed
     */
    public function decrypt($token);
}
