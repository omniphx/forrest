<?php

namespace Omniphx\Forrest\Interfaces;

interface FormatterInterface
{
    /**
     * @return array<string|int, string|string[]>
     */
    public function setHeaders();

    /**
     * @param string|array $data
     * @return string
     */
    public function setBody($data);

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return string|array
     */
    public function formatResponse($response);

    /**
     * @return string
     */
    public function getDefaultMIMEType();
}