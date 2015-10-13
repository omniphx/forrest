<?php

namespace spec\Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UserPasswordSpec extends ObjectBehavior
{
    public function let(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        RedirectInterface $mockedRedirect,
        InputInterface $mockedInput,
        EventInterface $mockedEvent)
    {
        $settings = [
            'authenticationFlow' => 'UserPassword',
            'credentials'        => [
                'consumerKey'    => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'    => 'callbackURL',
                'loginURL'       => 'https://login.salesforce.com',
                'username'       => '',
                'password'       => '',

            ],
            'parameters' => [
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',
            ],
            'instanceURL'  => '',
            'authRedirect' => 'redirectURL',
            'version'      => '30.0',
            'defaults'     => [
                'method'          => 'get',
                'format'          => 'json',
                'compression'     => false,
                'compressionType' => 'gzip',
            ],
            'language' => 'en_US',
        ];

        $mockedStorage->get('resources')->willReturn([
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
            'appMenu'      => '/services/data/v30.0/appMenu', ]);
        $mockedStorage->get('version')->willReturn([
            'url' => 'resourceURLs', ]);
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'instance_url' => 'https://na00.salesforce.com',
            'token_type'   => 'Oauth', ]);
        $mockedStorage->putTokenData(Argument::any())->willReturn(null);
        $mockedStorage->put(Argument::any(), Argument::any())->willReturn(null);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedClient->post(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedResponse);

        $this->beConstructedWith(
            $mockedClient,
            $mockedStorage,
            $mockedRedirect,
            $mockedInput,
            $mockedEvent,
            $settings);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\UserPassword');
    }

    public function it_should_authenticate(
        ResponseInterface $versionResponse,
        ClientInterface $mockedClient)
    {
        $mockedClient->send(Argument::any())->shouldBeCalled(1)->willReturn($versionResponse);

        $versionResponse->json()->shouldBeCalled()->willReturn([['version' => '30.0'],['version' => '31.0']]);

        $this->authenticate('url')->shouldReturn(null);
    }

    public function it_should_refresh(
        ResponseInterface $mockedResponse,
        StorageInterface $mockedStorage)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn(['key' => 'value']);
        $mockedStorage->putTokenData(Argument::type('array'))->shouldBeCalled();

        $this->refresh()->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('worked');

        $this->request('url', ['key' => 'value'])->shouldReturn('worked');
    }

    public function it_should_refresh_the_token_if_response_throws_error(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->send($mockedRequest)->willThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException');

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Since we are calling the send() method twice, the behavior is correct for it to throw an exception. Actual behavior would never throw the exception, it would return a response.
        $this->shouldThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException')->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_revoke_the_authentication_token(ClientInterface $mockedClient)
    {
        $mockedClient->post(Argument::type('string'), Argument::type('array'))->shouldBeCalled();
        $this->revoke()->shouldReturn(null);
    }

    //Client

    public function it_should_return_the_versions(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn(['version' => '29.0','version' => '30.0']);

        $this->versions()->shouldReturn(['version' => '29.0','version' => '30.0']);
    }

    public function it_should_return_resources(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    public function it_should_return_identity(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->willReturn('Identity');

        $this->identity()->shouldReturn('Identity');
    }

    public function it_should_return_limits(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);
        $mockedResponse->json()->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    public function it_should_return_describe(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);
        $mockedResponse->json()->shouldBeCalled()->willReturn('describe');

        $this->describe()->shouldReturn('describe');
    }

    public function it_should_return_query(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    public function it_should_return_query_next(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->next('nextUrl')->shouldReturn('query');
    }

    public function it_should_return_queryExplain(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    public function it_should_return_queryAll(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    public function it_should_return_quickActions(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    public function it_should_return_search(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    public function it_should_return_ScopeOrder(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->scopeOrder()->shouldReturn('searchScopeOrder');
    }

    public function it_should_return_searchLayouts(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    public function it_should_return_suggestedArticles(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('suggestedArticles');

        $this->suggestedArticles('query')->shouldReturn('suggestedArticles');
    }

    public function it_should_return_suggestedQueries(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->suggestedQueries('query')->shouldReturn('searchSuggestedQueries');
    }

    //Resource class

    public function it_returns_a_resource(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::type('string'), Argument::type('string'), Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');
        $mockedResponse->xml()->shouldBeCalled()->willReturn('xmlResource');

        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'abc',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $this->request('uri', [])->shouldReturn('jsonResource');
        $this->request('uri', ['format' => 'xml'])->shouldReturn('xmlResource');
    }

    public function it_allows_access_to_the_guzzle_client(
        ClientInterface $mockedClient)
    {
        $this->getClient()->shouldReturn($mockedClient);
    }
}
