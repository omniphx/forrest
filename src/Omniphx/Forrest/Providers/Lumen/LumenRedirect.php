<?php

namespace Omniphx\Forrest\Providers\Lumen;

use Illuminate\Http\Request as LumenRedirector;
use Laravel\Lumen\Http\Redirector;
use Omniphx\Forrest\Interfaces\RedirectInterface;

class LumenRedirect implements RedirectInterface
{
    protected $redirector;

    public function __construct(Redirector $redirector)
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