<?php

namespace spec\Omniphx\Forrest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

class RESTClientSpec extends ObjectBehavior
{

	function let(ClientInterface $mockedClient, SessionInterface $mockedSession, RedirectInterface $mockedRedirect, InputInterface $mockedInput)
	{
		$settings  = [
			'clientId' => 'testingClientId',
			'clientSecret' => 'testingClientSecret',
			'redirectURI' => 'callbackURL',
			'loginURI' => 'https://login.salesforce.com',
			'authRedirect' => 'redirectURL'];
		$this->beConstructedWith($mockedClient,$mockedSession,$mockedRedirect,$mockedInput,$settings);
	}

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\RESTClient');
    }

    function it_should_authenticate(RedirectInterface $mockedRedirect)
    {
    	$mockedRedirect->to(Argument::any())->willReturn('redirectURL');
    	$this->authenticate()->shouldReturn('redirectURL');
    }

    function it_should_return_a_resource(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
    	$mockedSession->get('token')->shouldBeCalled(1)->willReturn(['access_token'=>'asdfasdf','instance_url'=>'bligtyblopitydoo']);

        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);
        $mockedResponse->json()->shouldBeCalled(1)->willReturn('jsonResponse');
        $mockedResponse->xml()->shouldBeCalled(1)->willReturn('xmlResponse');

        $options1 = ['method'=>'GET','format'=>'JSON'];
    	$this->resource('string',$options1)->shouldReturn('jsonResponse');

        $options2 = ['method'=>'GET','format'=>'XML'];
        $this->resource('string',$options2)->shouldReturn('xmlResponse');

    }

    function it_should_callback(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse,
        InputInterface $mockedInput,
        RedirectInterface $mockedRedirect)
    {
        $mockedInput->get('code')->shouldBeCalled(1)->willReturn('this code');
        $mockedInput->get('state')->shouldBeCalled(1)->willReturn('this state');

        $mockedClient->post(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

    	$mockedResponse->json()->shouldBeCalled()->willReturn(array('version1','version2'));

        $mockedRedirect->to(Argument::type('string'))->willReturn('redirectURL');

    	$this->callback($mockedClient)->shouldReturn('redirectURL');
    }

    function it_should_get_the_user_info(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('token')->willReturn(['access_token'=>'asdfasdf','id'=>'bligtyblopitydoo']);
        $mockedClient->get(Argument::type('string'),Argument::type('array'))->willReturn($mockedResponse);
        $mockedResponse->json()->willReturn('The User!');

        $this->getUser()->shouldReturn('The User!');
    }

    function it_should_revoke_the_authentication_token(
        RedirectInterface $mockedRedirect)
    {
        $mockedRedirect->to(Argument::type('string'))->shouldBeCalled()->willReturn('redirectURL');
        $this->revoke()->shouldReturn('redirectURL');
    }

    function it_should_return_the_versions_resource(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get("token")->shouldBeCalled(1)->willReturn(['access_token'=>'asdfasdf','instance_url'=>'bligtyblopitydoo']);

        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedResponse->json()->shouldBeCalled(1)->willReturn('versions');

        $options = array('method'=>'GET','format'=>'JSON');
        $this->versions($options)->shouldReturn('versions');
    }

    function it_should_return_version_resource(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get("resources")->shouldBeCalled(1);
        $mockedSession->get("token")->shouldBeCalled(1)->willReturn(['access_token'=>'asdfasdf','instance_url'=>'bligtyblopitydoo']);

        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedResponse->json()->shouldBeCalled(1)->willReturn('appMenu');

        $options = array('method'=>'GET','format'=>'JSON');
        $this->appMenu($options)->shouldReturn('appMenu');
    }

    function it_should_return_sobject_resource(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get("resources")->shouldBeCalled(1);
        $mockedSession->get("token")->shouldBeCalled(1)->willReturn(['access_token'=>'asdfasdf','instance_url'=>'bligtyblopitydoo']);

        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedResponse->json()->shouldBeCalled(1)->willReturn('sobject');

        $options = array('method'=>'GET','format'=>'JSON');
        $this->sobject('Account',$options)->shouldReturn('sobject'); 
    }

    function it_should_return_the_appmenu_resource(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get("resources")->shouldBeCalled(1);
        $mockedSession->get("token")->shouldBeCalled(1)->willReturn(['access_token'=>'asdfasdf','instance_url'=>'bligtyblopitydoo']);

        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedResponse->json()->shouldBeCalled(1)->willReturn('appMenu');

        $options = array('method'=>'GET','format'=>'JSON');
        $this->appMenu($options)->shouldReturn('appMenu');
    }

    function letGo()
    {
        //Let go any resources
    }
}
