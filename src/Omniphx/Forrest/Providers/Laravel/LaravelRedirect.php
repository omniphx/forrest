<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\RedirectInterface;

class LaravelRedirect implements RedirectInterface
{
    protected $redirect;

    public function __construct()
    {
        $this->redirect = app('redirect');
    }

    /**
     * Redirect to new url.
     *
     * @param string $parameter
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($parameter)
    {
        return $this->redirect->to($parameter);
    }
}
