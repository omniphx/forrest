<?php

namespace Omniphx\Forrest\Formatters;

use Omniphx\Forrest\Interfaces\FormatterInterface;

class CsvHeadersFormatter implements FormatterInterface
{
    protected $tokenRepository;
    protected $settings;
    protected $headers;
    protected $mimeType = 'application/json';
    protected $acceptMimeType = 'text/csv';

    public function __construct($tokenRepository, $settings) {
        $this->tokenRepository = $tokenRepository;
        $this->settings = $settings;
    }

    public function setHeaders()
    {
        $accessToken = $this->tokenRepository->get()['access_token'];
        $tokenType   = $this->tokenRepository->get()['token_type'];

        $this->headers['Accept']        = $this->getDefaultAcceptMIMEType();
        $this->headers['Content-Type']  = $this->getDefaultMIMEType();
        $this->headers['Authorization'] = "$tokenType $accessToken";

        $this->setCompression();

        return $this->headers;
    }

    private function setCompression()
    {
        if (!$this->settings['defaults']['compression']) return;

        $this->headers['Accept-Encoding']  = $this->settings['defaults']['compressionType'];
        $this->headers['Content-Encoding'] = $this->settings['defaults']['compressionType'];
    }

    public function setBody($data)
    {
        return $data;
    }

    public function formatResponse($response)
    {
        $body = $response->getBody();
        $header = $response->getHeaders();
        $contents = (string) $body;
        
        return [ 
            'header' => $header,
            'body' => $contents,
            
        ];
    }

    public function getDefaultMIMEType()
    {
        return $this->mimeType;
    }

    public function getDefaultAcceptMIMEType()
    {
        return $this->acceptMimeType;
    }
}
