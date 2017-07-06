<?php

namespace Omniphx\Forrest\Interfaces;

interface FormatterInterface
{
    public function setHeaders();
    public function setBody($data);
    public function formatResponse($response);
}