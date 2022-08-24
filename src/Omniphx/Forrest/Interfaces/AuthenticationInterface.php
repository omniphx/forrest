<?php

namespace Omniphx\Forrest\Interfaces;

interface AuthenticationInterface
{
    /**
     * Begin authentication process.
     *
     * @param string|null $url
     *
     * @return \Illuminate\Http\RedirectResponse|void
     */
    public function authenticate($url);

    /**
     * Refresh authentication token.
     *
     * @return void
     */
    public function refresh();

    /**
     * Revokes authentication token.
     *
     * @return \Psr\Http\Message\ResponseInterface|void $response
     */
    public function revoke();
}
