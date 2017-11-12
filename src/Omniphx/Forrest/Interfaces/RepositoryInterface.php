<?php

namespace Omniphx\Forrest\Interfaces;

interface RepositoryInterface
{
    public function get();
    public function has();
    public function put($item);
}
