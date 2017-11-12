<?php

namespace spec\Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Repositories\ResourceRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingResourceException;

class ResourceRepositorySpec extends ObjectBehavior
{
    function let(StorageInterface $mockedStorage) {
        $this->beConstructedWith($mockedStorage);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ResourceRepository::class);
    }

    function it_should_store_resource($mockedStorage) {
        $mockedStorage->put('resources', 'resources')->shouldBeCalled();

        $this->put('resources');
    }

    function it_should_get_resource($mockedStorage) {
        $mockedStorage->has('resources')->shouldBeCalled()->willReturn(true);
        $mockedStorage
            ->get('resources')
            ->shouldBeCalled()
            ->willReturn(['resource' => 'resources']);

        $this->get('resource')->shouldReturn('resources');
    }

    function it_should_throw_exception_if_resource_doesnt_exist($mockedStorage) {
        $mockedStorage->has('resources')->shouldBeCalled()->willReturn(false);
        $missingResourcesException = new MissingResourceException('No resources available');

        $this->shouldThrow($missingResourcesException)->duringGet('resource');
    }
}
