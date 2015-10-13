<?php

namespace Omniphx\Forrest\Interfaces;

interface InputInterface
{
    /**
     * Get input from response.
     *
     * @param string $parameter
     *
     * @return mixed
     */
    public function get($parameter);
}
