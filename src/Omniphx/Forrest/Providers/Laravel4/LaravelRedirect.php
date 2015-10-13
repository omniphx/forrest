<?php

namespace Omniphx\Forrest\Providers\Laravel4;

use Omniphx\Forrest\Interfaces\RedirectInterface;
use Redirect;

class LaravelRedirect implements RedirectInterface
{
    /**
     * Redirect to new url.
     *
     * @param string $parameter
     *
     * @return void
     */
    public function to($parameter)
    {
        return Redirect::to($parameter);
    }
}
