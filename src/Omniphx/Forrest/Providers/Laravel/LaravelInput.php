<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Illuminate\Http\Request;
use Omniphx\Forrest\Interfaces\InputInterface;

class LaravelInput implements InputInterface
{
    /**
     * @var Request
     */
    protected $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Get input from response.
     *
     * @param string $parameter
     *
     * @return mixed
     */
    public function get($parameter)
    {
        return $this->request->input($parameter);
    }
}
