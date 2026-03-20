<?php

namespace Tests\Fixtures;

use Omniphx\Forrest\Client;

class InspectableClient extends Client
{
    public $refreshCalls = 0;

    public function authenticate($url = null)
    {
        return null;
    }

    public function refresh()
    {
        $this->refreshCalls++;
    }

    public function revoke()
    {
        return null;
    }
}
