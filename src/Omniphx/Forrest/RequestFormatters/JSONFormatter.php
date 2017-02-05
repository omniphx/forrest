<?php

namespace Omniphx\Forrest\RequestFormatters;

use Omniphx\Forrest\Interfaces\RequestFormatterInterface;

public class JSONFormatter implements RequestFormatterInterface
{
    public function setHeaders()
    {
        $headers['Accept'] = 'application/json';
        $headers['Content-Type'] = 'application/json';

        return $headers;
    }

    public function setBody($data)
    {
        return json_encode($data);
    }

    public function formatResponse($response)
    {
        return json_decode($response->getBody(), true);
    }
}