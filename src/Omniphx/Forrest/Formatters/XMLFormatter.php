<?php

namespace Omniphx\Forrest\Formatters;

use Omniphx\Forrest\Interfaces\FormatterInterface;

class XMLFormatter implements FormatterInterface
{
    const MIME_TYPE = 'application/xml';

    public function setHeaders()
    {
        $headers['Accept'] = $this->getDefaultMIMEType();
        $headers['Content-Type'] = $this->getDefaultMIMEType();

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

    public function getDefaultMIMEType()
    {
        return MIME_TYPE;
    }
}