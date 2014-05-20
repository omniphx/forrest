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
		$settings  = array(
            'oauth' => array(

                'clientId'     => 'testingClientId',
                'clientSecret' => 'testingClientSecret',
                'callbackURI'  => 'callbackURL',
                'loginURL'     => 'https://login.salesforce.com',

            ),
            'optional'     => array(

                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',

            ),
			'authRedirect' => 'redirectURL',
            'version' => '30.0',
            'defaults' => array(

                'method' => 'get',
                'format' => 'json',
                
            ),
        );


        $mockedSession->get('resources')->willReturn([
            'sobjects'     => '/services/data/v30.0/sobjects', 
            'connect'      => '/services/data/v30.0/connect',
            'query'        => '/services/data/v30.0/query',
            'theme'        => '/services/data/v30.0/theme',
            'queryAll'     => '/services/data/v30.0/queryAll',
            'tooling'      => '/services/data/v30.0/tooling',
            'chatter'      => '/services/data/v30.0/chatter',
            'analytics'    => '/services/data/v30.0/analytics',
            'recent'       => '/services/data/v30.0/recent',
            'process'      => '/services/data/v30.0/process',
            'identity'     => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'flexiPage'    => '/services/data/v30.0/flexiPage',
            'search'       => '/services/data/v30.0/search',
            'quickActions' => '/services/data/v30.0/quickActions',
            'appMenu'      => '/services/data/v30.0/appMenu']);

        $mockedSession->get('version')->willReturn([
            'url' => 'resourceURLs']);

        $mockedSession->getToken()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'instance_url' => 'https://na00.salesforce.com']);


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

    	$this->callback()->shouldReturn('redirectURL');
    }

    function it_should_revoke_the_authentication_token(
        ClientInterface $mockedClient,
        SessionInterface $mockedSession,
        RedirectInterface $mockedRedirect)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedClient->post(Argument::type('string'),Argument::type('array'))->shouldBeCalled();
        $mockedRedirect->to(Argument::type('string'))->shouldBeCalled()->willReturn('redirectURL');
        $this->revoke()->shouldReturn('redirectURL');
    }

    function it_should_return_the_versions(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versions');

        $this->versions()->shouldReturn('versions');
    }

    function it_should_return_resources(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('version')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    function it_should_return_identity (
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->willReturn('Identity');

        $this->identity()->shouldReturn('Identity');
    }

    function it_should_return_limits(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    function it_should_return_query(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    function it_should_return_queryExplain(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    function it_should_return_queryAll(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    function it_should_return_quickActions(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    function it_should_return_search(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    function it_should_return_ScopeOrder(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->scopeOrder()->shouldReturn('searchScopeOrder');
    }

    function it_should_return_searchLayouts(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    function it_should_return_suggestedArticles(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('suggestedArticles');

        $this->suggestedArticles('query')->shouldReturn('suggestedArticles');
    }

    function it_should_return_suggestedQueries(
        SessionInterface $mockedSession,
        ResourceInterface $mockedResource)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResource->request(Argument::type('string'),Argument::type('array'))->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->suggestedQueries('query')->shouldReturn('searchSuggestedQueries');
    }

    function letGo()
    {
        //Let go any resources
    }
}
