<?php

namespace Omniphx\Forrest\RequestFormatters;

use Omniphx\Forrest\Interfaces\RequestFormatterInterface;

class XMLFormatter implements RequestFormatterInterface
{
    public function setHeaders()
    {
        $headers['Accept'] = 'application/xml';
        $headers['Content-Type'] = 'application/xml';

        return $headers;
    }

    public function setBody($data)
    {
        return urlencode($data);
    }

    public function formatResponse($response)
    {
        $body = $response->getBody();
        $contents = (string) $body;
        $decodedXML = simplexml_load_string($contents);

        return $decodedXML;
    }
}