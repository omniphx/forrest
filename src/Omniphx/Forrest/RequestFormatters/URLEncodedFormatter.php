<?php

namespace Omniphx\Forrest\RequestFormatters;

use Omniphx\Forrest\Interfaces\RequestFormatterInterface;

public class URLEncodedFormatter implements RequestFormatterInterface
{
    public function setHeaders()
    {
        $headers['Accept'] = 'application/x-www-form-urlencoded';
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $headers;
    }

    public function setBody($data)
    {
        return $data;
    }

    public function formatResponse($response)
    {
        return $response->getBody();
    }
}
}