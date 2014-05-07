<?php

namespace spec\Omniphx\Forrest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;

class ResourceSpec extends ObjectBehavior
{
	function let(ClientInterface $mockedClient, SessionInterface $mockedSession)
	{
		$this->beConstructedWith($mockedClient,$mockedSession);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Resource');
    }
}
