<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use Illuminate\Routing\Redirector;
use PhpSpec\ObjectBehavior;

class LaravelRedirectSpec extends ObjectBehavior
{
    public function let(Redirector $redirect)
    {
        $this->beConstructedWith($redirect);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelRedirect');
    }
}
