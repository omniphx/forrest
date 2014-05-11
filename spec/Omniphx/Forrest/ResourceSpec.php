<?php

namespace spec\Omniphx\Forrest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

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

	function it_returns_a_resource(
		ClientInterface $mockedClient,
		SessionInterface $mockedSession,
		RequestInterface $mockedRequest,
		ResponseInterface $mockedResponse)
	{
		$mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');
        $mockedResponse->xml()->shouldBeCalled()->willReturn('xmlResource');

		$mockedSession->getToken()->willReturn(array('access_token'=>'abc', 'instance_url'=>'def'));

		$this->request('uri',['method'=>'get','format'=>'json'])->shouldReturn('jsonResource');
		$this->request('uri',['method'=>'get','format'=>'xml'])->shouldReturn('xmlResource');
	}

}
