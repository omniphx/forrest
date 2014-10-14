<?php

namespace spec\Omniphx\Forrest\Authentications;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\RequestInterface;
use Omniphx\Forrest\Interfaces\SessionInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;

class WebServerSpec extends ObjectBehavior
{
    function let(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        SessionInterface $mockedSession,
        RedirectInterface $mockedRedirect,
        InputInterface $mockedInput)
    {
        $settings  = array(
            'oauth' => array(

                'consumerKey'     => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'  => 'callbackURL',
                'loginURL'     => 'https://login.salesforce.com',

            ),
            'authenticationFlow' => 'WebServer',
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
                'debug' => false,
    
            ),
            'language' => 'en_US'
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
            'instance_url' => 'https://na00.salesforce.com',
            'token_type'   => 'Oauth']);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);

        $this->beConstructedWith(
            $mockedClient,
            $mockedSession,
            $mockedRedirect,
            $mockedInput,
            $settings);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\WebServer');
    }

    function it_should_authenticate(RedirectInterface $mockedRedirect)
    {
        $mockedRedirect->to(Argument::any())->willReturn('redirectURL');
        $this->authenticate()->shouldReturn('redirectURL');
    }

    function it_should_callback(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse1,
        ResponseInterface $mockedResponse2,
        InputInterface $mockedInput,
        SessionInterface $mockedSession)
    {
        $mockedInput->get('code')->shouldBeCalled(1)->willReturn('this code');
        $mockedInput->get('state')->shouldBeCalled(1)->willReturn('this state');

        $mockedClient->post('https://login.salesforce.com/services/oauth2/token',Argument::type('array'))->shouldBeCalled(1)->willReturn($mockedResponse1);

        $mockedClient->createRequest('get', 'https://na00.salesforce.comresourceURLs', Argument::type('array'))->shouldBeCalled(1)->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->shouldBeCalled(1)->willReturn($mockedResponse2);

        $mockedResponse1->json()->shouldBeCalled(1)->willReturn(array(
            'access_token'  => 'value1',
            'refresh_token' => 'value2'));

        $mockedResponse2->json()->shouldBeCalled()->willReturn([['version'=>'30.0'],['version'=>'31.0']]);

        $mockedSession->putToken(Argument::type('array'))->shouldBeCalled();
        $mockedSession->putRefreshToken(Argument::exact('value2'))->shouldBeCalled();
        $mockedSession->put(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->callback()->shouldReturn(null);
    }

    function it_should_refresh(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {

        $mockedClient->post('https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $this->refresh('token')->shouldReturn($mockedResponse);

    }

    //Client abstraction

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
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();

        $mockedResponse->json()->shouldBeCalled()->willReturn(array('version'=>'29.0','version'=>'30.0'));

        $this->versions()->shouldReturn(array('version'=>'29.0','version'=>'30.0'));
    }

    function it_should_return_resources(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('version')->shouldBeCalled();

        $mockedResponse->json()->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    function it_should_return_identity (
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedResponse->json()->willReturn('Identity');

        $this->identity()->shouldReturn('Identity');
    }

    function it_should_return_limits(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResponse->json()->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    function it_should_return_describe(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResponse->json()->shouldBeCalled()->willReturn('describe');

        $this->describe()->shouldReturn('describe');        
    }

    function it_should_return_query(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    function it_should_return_queryExplain(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    function it_should_return_queryAll(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    function it_should_return_quickActions(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    function it_should_return_search(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    function it_should_return_ScopeOrder(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->scopeOrder()->shouldReturn('searchScopeOrder');
    }

    function it_should_return_searchLayouts(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    function it_should_return_suggestedArticles(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('suggestedArticles');

        $this->suggestedArticles('query')->shouldReturn('suggestedArticles');
    }

    function it_should_return_suggestedQueries(
        SessionInterface $mockedSession,
        ResponseInterface $mockedResponse)
    {
        $mockedSession->getToken()->shouldBeCalled();
        $mockedSession->get('resources')->shouldBeCalled();
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->suggestedQueries('query')->shouldReturn('searchSuggestedQueries');
    }

    //Resource
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

        $mockedSession->getToken()->willReturn(array(
            'access_token' =>'abc',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $this->request('uri',[])->shouldReturn('jsonResource');
        $this->request('uri',['format'=>'xml'])->shouldReturn('xmlResource');
    }

}


