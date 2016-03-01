<?php

namespace Omniphx\Forrest\Exceptions;

use GuzzleHttp\Exception\RequestException;

class TokenExpiredException extends RequestException
{
    public function __construct($message, RequestException $e)
    {
        parent::__construct($message, $e->getRequest(), $e->getResponse(), $e);
    }
}
