<?php

namespace Omniphx\Forrest\Providers\Laravel4;

use Crypt;
use Omniphx\Forrest\Exceptions\MissingRefreshTokenException;
use Omniphx\Forrest\Exceptions\MissingTokenException;
use Omniphx\Forrest\Interfaces\StorageInterface;

abstract class LaravelStorageProvider implements StorageInterface
{
    /**
     * Encrypt authentication token and store it in session.
     *
     * @param array $token
     *
     * @return void
     */
    public function putTokenData($token)
    {
        $encryptedToken = Crypt::encrypt($token);

        return $this->put('token', $encryptedToken);
    }

    /**
     * Get token from the session and decrypt it.
     *
     * @return mixed
     */
    public function getTokenData()
    {
        if ($this->has('token')) {
            $token = $this->get('token');

            return Crypt::decrypt($token);
        }

        throw new MissingTokenException(sprintf('No token available in \''.\Config::get('forrest::config.storage.type').'\' storage'));
    }

    /**
     * Encrypt refresh token and pass into session.
     *
     * @param array $token
     *
     * @return void
     */
    public function putRefreshToken($token)
    {
        $encryptedToken = Crypt::encrypt($token);

        return $this->put('refresh_token', $encryptedToken);
    }

    /**
     * Get refresh token from session and decrypt it.
     *
     * @return mixed
     */
    public function getRefreshToken()
    {
        if ($this->has('refresh_token')) {
            $token = $this->get('refresh_token');

            return Crypt::decrypt($token);
        }

        throw new MissingRefreshTokenException(sprintf('No refresh token stored in current session. Verify you have added refresh_token to your scope items on your connected app settings in Salesforce.'));
    }
}
