<?php

namespace spec\Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Repositories\StateRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingStateException;

class StateRepositorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType(StateRepository::class);
    }

    function let(StorageInterface $mockedStorage)
    {
        $this->beConstructedWith($mockedStorage);
    }

    function it_should_store_state($mockedStorage)
    {
        $mockedStorage->put('stateOptions','foo')->shouldBeCalled();

        $this->put('foo');
    }

    function it_should_get_state($mockedStorage)
    {
        $mockedStorage->has('stateOptions')->shouldBeCalled()->willReturn(true);
        $mockedStorage->get('stateOptions')->shouldBeCalled()->willReturn('foo');

        $this->get()->shouldReturn('foo');
    }

    function it_should_throw_an_error_if_storage_does_not_have_state($mockedStorage)
    {
        $mockedStorage->has('stateOptions')->shouldBeCalled()->willReturn(false);

        $missingStateException = new MissingStateException('No state available');

        $this->shouldThrow($missingStateException)->duringGet();

    }
}
