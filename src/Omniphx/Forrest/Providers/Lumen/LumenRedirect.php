<?php

namespace Omniphx\Forrest\Providers\Lumen;

use Laravel\Lumen\Http\Redirector as LumenRedirector;
// use Illuminate\Http\Request as LumenRedirector;
use Omniphx\Forrest\Interfaces\RedirectInterface;

class LumenRedirect implements RedirectInterface
{
    protected $redirector;

    public function __construct(LumenRedirector $redirector)
    {
        $this->redirector = $redirector;
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
        return $this->redirector->to($parameter);
    }
}