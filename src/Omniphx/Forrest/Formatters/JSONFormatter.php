<?php

namespace Omniphx\Forrest\Formatters;

use Omniphx\Forrest\Interfaces\FormatterInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;

class JSONFormatter implements FormatterInterface
{
    protected $tokenRepository;
    protected $settings;
    protected $headers;
    protected $mimeType = 'application/json';

    public function __construct(RepositoryInterface $tokenRepository, $settings) {
        $this->tokenRepository = $tokenRepository;
        $this->settings = $settings;
    }

    public function setHeaders()
    {
        $accessToken = $this->tokenRepository->get()['access_token'];
        $tokenType   = $this->tokenRepository->get()['token_type'];

        $this->headers['Accept']        = $this->getDefaultMIMEType();
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
        return json_encode($data);
    }

    public function formatResponse($response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    public function getDefaultMIMEType()
    {
        return $this->mimeType;
    }
}