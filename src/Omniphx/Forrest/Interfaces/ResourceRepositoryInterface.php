<?php

namespace Omniphx\Forrest\Interfaces;

interface ResourceRepositoryInterface
{
    public function get($resource);
    public function put($resource);
    public function has();
}
