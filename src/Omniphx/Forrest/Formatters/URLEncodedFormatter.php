<?php

namespace Omniphx\Forrest\Formatters;

use Omniphx\Forrest\Interfaces\FormatterInterface;

class URLEncodedFormatter implements FormatterInterface
{
    protected $mimeType = 'application/x-www-form-urlencoded';

    public function setHeaders()
    {
        $headers['Accept'] = $this->getDefaultMIMEType();
        $headers['Content-Type'] = $this->getDefaultMIMEType();

        return $headers;
    }

    public function setBody($data)
    {
        return $data;
    }

    public function formatResponse($response)
    {
        return $response->getBody()->getContents();
    }

    public function getDefaultMIMEType()
    {
        return $this->mimeType;
    }
}