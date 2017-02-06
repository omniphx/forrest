<?php

namespace Omniphx\Forrest\Interfaces;

interface RequestFormatterInterface
{
    public function setHeaders();
    public function setBody($data);
    public function formatResponse($response);
}