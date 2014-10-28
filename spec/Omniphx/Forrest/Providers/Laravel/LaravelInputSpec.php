<?php

namespace spec\Omniphx\Forrest\Providers\Laravel;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Input;

class LaravelInputSpec extends ObjectBehavior
{

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Providers\Laravel\LaravelInput');
    }

}
