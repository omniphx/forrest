<?php

namespace spec\Omniphx\Forrest;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Omniphx\Forrest\Interfaces\ResourceInterface;
use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;

class RESTClientSpec extends ObjectBehavior
{

	function let(ResourceInterface $mockedResource, ClientInterface $mockedClient, SessionInterface $mockedSession, RedirectInterface $mockedRedirect, InputInterface $mockedInput)
	{
		$settings  = [
			'clientId' => 'testingClientId',
			'clientSecret' => 'testingClientSecret',
			'redirectURI' => 'callbackURL',
			'loginURI' => 'https://login.salesforce.com',
            'optional' => [
                'display' => 'popup',
                'immediate' => 'false',
                'state' => '',
                'scope' => ''],
			'authRedirect' => 'redirectURL'];
		$this->beConstructedWith($mockedResource, $mockedClient,$mockedSession,$mockedRedirect,$mockedInput,$settings);
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

    function it_should_callback(
        ClientInterface $mockedClient,
        ResourceInterface $mockedResource,
        SessionInterface $mockedSession,
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

        $mockedResource->request(Argument::type('string'),Argument::type('array'))->willReturn(array('version1','version2'));

        $mockedRedirect->to(Argument::type('string'))->willReturn('redirectURL');

        $mockedSession->get('version')->willReturn(array('url'=>'sampleURL'));
        $mockedSession->putToken(Argument::type('array'))->shouldBeCalled();
        $mockedSession->put('version',Argument::type('string'))->shouldBeCalled();
        $mockedSession->put('resources',Argument::type('array'))->shouldBeCalled();

    	$this->callback()->shouldReturn('redirectURL');
    }

    function it_should_get_the_user_info(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->willReturn(['access_token'=>'asdfasdf','id'=>'bligtyblopitydoo']);
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

    function it_should_return_the_versions_resource(ResourceInterface $mockedResource)
    {   
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versions');

        $this->versions()->shouldReturn('versions');
    }

    function it_should_return_resources_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    function it_should_return_sobject_resource(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled()->willReturn(array('sobjects'=>'resourceURI'));
        $mockedResource->request('resourceURI/Account',Argument::type('array'))->shouldBeCalled()->willReturn('sObject');

        $this->sObject('Account')->shouldReturn('sObject');
    }

    function it_should_return_the_appmenu_resource(
        ResourceInterface $mockedResource,
        SessionInterface $mockedSession)
    {
        $mockedSession->get("resources")->shouldBeCalled()->willReturn(array('appMenu'=>'resourceURI'));
        $mockedResource->request('resourceURI/AppSwitcher/',Argument::type('array'))->shouldBeCalled()->willReturn('appMenu');

        $this->appMenu()->shouldReturn('appMenu');
    }



    function letGo()
    {
        //Let go any resources
    }
}
