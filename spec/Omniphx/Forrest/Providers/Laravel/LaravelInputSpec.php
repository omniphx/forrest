<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use PhpSpec\ObjectBehavior;

class LaravelInputSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelInput');
    }
}
