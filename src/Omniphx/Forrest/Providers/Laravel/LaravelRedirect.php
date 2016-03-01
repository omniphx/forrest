<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\RedirectInterface;
use Redirect;

class LaravelRedirect implements RedirectInterface
{
    /**
     * Redirect to new url.
     *
     * @param string $parameter
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function to($parameter)
    {
        return Redirect::to($parameter);
    }
}
