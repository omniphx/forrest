<?php

namespace Omniphx\Forrest\Interfaces;

interface UserPasswordSoapInterface extends AuthenticationInterface
{
    /**
     * Begin specific user authentication process.
     *
     * @param string $url
     * @param string $username,$password
     * @param string $password
     *
     * @return mixed
     */
    public function authenticateUser($url, $username, $password);
}
