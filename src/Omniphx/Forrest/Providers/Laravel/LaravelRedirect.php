<?php

namespace Omniphx\Forrest\Providers\Laravel;

use Omniphx\Forrest\Interfaces\RedirectInterface;

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
        if (function_exists('redirect')) {
            return redirect($parameter);
        }
        return Redirect::to($parameter);
    }
}
