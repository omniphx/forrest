<?php

namespace Omniphx\Forrest\Interfaces;

interface AuthenticationInterface
{
    /**
     * Begin authentication process.
     *
     * @param string $url
     *
     * @return mixed
     */
    public function authenticate($url);

    /**
     * Refresh authentication token.
     *
     * @return mixed $response
     */
    public function refresh();

    /**
     * Revokes authentication token.
     *
     * @return mixed $response
     */
    public function revoke();
}
