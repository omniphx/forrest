<?php namespace Omniphx\Forrest\Interfaces;

interface UserPasswordInterface extends AuthenticationInterface{

    /**
     * Refresh authentication token
     * @return mixed $response
     */
    public function refresh();

}