<?php

namespace Omniphx\Forrest\Interfaces;

interface RedirectInterface
{
    /**
     * Redirect to new url.
     *
     * @param string $parameter
     *
     * @return void
     */
    public function to($parameter);
}
