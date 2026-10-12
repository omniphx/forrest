<?php

namespace Omniphx\Forrest\Exceptions;

use GuzzleHttp\Exception\RequestException;

class SalesforceException extends RequestException
{
    use WrapsRequestException;
}
