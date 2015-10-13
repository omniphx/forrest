<?php

namespace Omniphx\Forrest\Providers\Laravel4;

use Input;
use Omniphx\Forrest\Interfaces\InputInterface;

class LaravelInput implements InputInterface
{
    /**
     * Get input from response.
     *
     * @param string $parameter
     *
     * @return mixed
     */
    public function get($parameter)
    {
        return Input::get($parameter);
    }
}
