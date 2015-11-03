<?php

namespace spec\Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class WebServerSpec extends ObjectBehavior
{
    protected $versionJSON = '[{"label":"Winter \'16","url":"/services/data/v35.0","version":"35.0"}]';

    protected $authenticationJSON = '{"access_token":"00Do0000000secret","instance_url":"https://na17.salesforce.com","id":"https://login.salesforce.com/id/00D","token_type":"Bearer","issued_at":"1447000236011","signature":"secretsig","refresh_token":"refreshToken"}';

    protected $responseJSON = '{"foo":"bar"}';

    protected $responseXML = "<meseek><intro>I'm Mr. Meseeks, look at me!</intro><role>Get 2 strokes off Gary's golf swing</role><solution>Has he tried keeping his shoulder's square?</solution></meseek>";

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
            'credentials' => [
                'consumerKey'     => 'testingClientId',
                'consumerSecret'  => 'testingClientSecret',
                'callbackURI'     => 'callbackURL',
                'loginURL'        => 'https://login.salesforce.com',
            ],
            'authenticationFlow' => 'WebServer',
            'parameters'         => [
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',
                'prompt'    => '',
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

        $resources = [
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
            'identity'     => 'https://login.salesforce.com/id/00D',
            'flexiPage'    => '/services/data/v30.0/flexiPage',
            'search'       => '/services/data/v30.0/search',
            'quickActions' => '/services/data/v30.0/quickActions',
            'appMenu'      => '/services/data/v30.0/appMenu', ];

        //Storage stubs
        $mockedStorage->get('resources')->willReturn($resources);
        $mockedStorage->get('version')->willReturn([
            'url'     => '/services/data/v35.0',
            'version' => '35.0']);
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00D',
            'instance_url' => 'https://na00.salesforce.com',
            'token_type'   => 'Oauth', ]);

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
        $this->shouldHaveType('Omniphx\Forrest\Authentications\WebServer');
    }

    public function it_should_authenticate(RedirectInterface $mockedRedirect)
    {
        $mockedRedirect->to(Argument::any())->willReturn('redirectURL');
        $this->authenticate()->shouldReturn('redirectURL');
    }

    public function it_should_callback(
        ClientInterface $mockedClient,
        ClientInterface $mockedClient2,
        RequestInterface $mockedRequest,
        ResponseInterface $tokenResponse,
        ResponseInterface $versionResponse,
        ResponseInterface $simpleResponse,
        StorageInterface $mockedStorage)
    {
        $mockedClient->request('post','https://login.salesforce.com/services/oauth2/token', ["form_params" => ["code" => null, "grant_type" => "authorization_code", "client_id" => "testingClientId", "client_secret" => "testingClientSecret", "redirect_uri" => "callbackURL"]])->shouldBeCalled()->willReturn($tokenResponse);
        $tokenResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationJSON);

        $mockedClient->request('get','https://na00.salesforce.com', ["headers" => ["Authorization" => "Oauth accessToken", "Accept" => "application/json", "Content-Type" => "application/json"]])->shouldBeCalled()->willReturn($simpleResponse);
        $simpleResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $mockedStorage->putTokenData(["access_token" => "00Do0000000secret", "instance_url" => "https://na17.salesforce.com", "id" => "https://login.salesforce.com/id/00D", "token_type" => "Bearer", "issued_at" => "1447000236011", "signature" => "secretsig", "refresh_token" => "refreshToken"])->shouldBeCalled();
        $mockedStorage->putRefreshToken('refreshToken')->shouldBeCalled();
        $mockedStorage->get('version')->willReturn(null);

        $mockedStorage->put("resources", ["foo" => "bar"])->shouldBeCalled();

        $this->callback()->shouldReturn(null);
    }

    public function it_should_refresh(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        StorageInterface $mockedStorage)
    {
        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');

        $mockedClient->refresh('get','https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn(['key' => 'value']);

        $mockedStorage->putTokenData(Argument::type('array'))->shouldBeCalled();

        $this->refresh('token')->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);

        $mockedResponse->json()->shouldBeCalled()->willReturn('worked');

        $this->request('url', ['key' => 'value'])->shouldReturn('worked');
    }

    public function it_should_refresh_the_token_if_response_throws_error(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedClient->send($mockedRequest)->willThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException');

        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');

        $mockedClient->post('https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled(1)->willReturn(['key' => 'value']);

        $mockedStorage->putTokenData(Argument::type('array'))->shouldBeCalled();

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Since we are calling the send() method twice, the behavior is correct for it to throw an exception. Actual behavior would never throw the exception, it would return a response.
        $this->shouldThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException')->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_not_call_refresh_method_if_there_is_no_token(
        ClientInterface $mockedClient,
        RequestInterface $failedRequest,
        StorageInterface $mockedStorage)
    {
        $mockedClient->send($failedRequest)->willThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException');

        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($failedRequest);

        $mockedStorage->getRefreshToken()->willThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException');

        $this->shouldThrow('Omniphx\Forrest\Exceptions\MissingRefreshTokenException')->duringRequest('url', ['key' => 'value']);
    }

    //Client class

    public function it_should_revoke_the_authentication_token(ClientInterface $mockedClient)
    {
        $mockedClient->post(Argument::type('string'), Argument::type('array'))->shouldBeCalled();
        $this->revoke()->shouldReturn(null);
    }

    public function it_should_return_the_versions(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn(['version' => '29.0','version' => '30.0']);

        $this->versions()->shouldReturn(['version' => '29.0','version' => '30.0']);
    }

    public function it_should_return_resources(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    public function it_should_return_identity(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->willReturn('Identity');

        $this->identity()->shouldReturn('Identity');
    }

    public function it_should_return_limits(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);
        $mockedResponse->json()->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    public function it_should_return_describe(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);
        $mockedResponse->json()->shouldBeCalled()->willReturn('describe');

        $this->describe()->shouldReturn('describe');
    }

    public function it_should_return_query(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    public function it_should_return_next_query(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->next('nextUrl')->shouldReturn('query');
    }

    public function it_should_return_queryExplain(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    public function it_should_return_queryAll(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    public function it_should_return_quickActions(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    public function it_should_return_search(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    public function it_should_return_ScopeOrder(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->scopeOrder()->shouldReturn('searchScopeOrder');
    }

    public function it_should_return_searchLayouts(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    public function it_should_return_suggestedArticles(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get', 'https://na##.salesforce.com/services/data/v30.0/search/suggestTitleMatches?q=query&language=en_US&publishStatus=Online&foo=bar&flim=flam', Argument::type('Array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('suggestedArticles');

        $this->suggestedArticles('query', ['parameters' => ['foo' => 'bar','flim' => 'flam']])->shouldReturn('suggestedArticles');
    }

    public function it_should_return_suggestedQueries(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get', 'https://na##.salesforce.com/services/data/v30.0/search/suggestSearchQueries?q=query&language=en_US&foo=bar&flim=flam', Argument::type('array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->suggestedQueries('query', ['parameters' => ['foo' => 'bar','flim' => 'flam']])->shouldReturn('searchSuggestedQueries');
    }

    public function it_should_return_custom_request(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get', 'https://na##.salesforce.com/services/apexrest/FieldCase?foo=bar', Argument::type('array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('customRequest');
        $this->custom('/FieldCase', ['parameters' => ['foo' => 'bar']])->shouldReturn('customRequest');
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

    public function it_should_format_header_in_json(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])
            ->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri', [])->shouldReturn('jsonResource');
    }

    public function it_should_format_header_in_xml(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/xml', 'Content-Type' => 'application/xml']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->xml()->shouldBeCalled()->willReturn('xmlResource');

        $this->request('uri', ['format' => 'xml'])->shouldReturn('xmlResource');
    }

    public function it_should_format_header_in_urlencoding(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/x-www-form-urlencoded', 'Content-Type' => 'application/x-www-form-urlencoded']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $this->request('uri', ['format' => 'urlencoded'])->shouldReturn($mockedResponse);
    }

    public function it_should_format_header_with_gzip(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/json', 'Content-Type' => 'application/json', 'Accept-Encoding' => 'gzip', 'Content-Encoding' => 'gzip']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri', ['compression' => true, 'compressionType' => 'gzip'])->shouldReturn('jsonResource');
    }

    public function it_should_format_header_with_deflate(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/json', 'Content-Type' => 'application/json', 'Accept-Encoding' => 'deflate', 'Content-Encoding' => 'deflate']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri', ['compression' => true, 'compressionType' => 'deflate'])->shouldReturn('jsonResource');
    }

    public function it_should_format_header_without_compression(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $mockedClient->createRequest(
            'get',
            'uri',
            ['headers' => ['Authorization' => 'bearer accesstoken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri', ['compression' => false])->shouldReturn('jsonResource');
    }

    public function it_should_allow_a_get_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('GET', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->get('uri')->shouldReturn($mockedResponse);
    }

    public function it_should_allow_a_post_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('POST', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->post('uri', ['test' => 'param'])->shouldReturn($mockedResponse);
    }

    public function it_should_allow_a_put_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('PUT', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->put('uri', ['test' => 'param'])->shouldReturn($mockedResponse);
    }

    public function it_should_allow_a_patch_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('PATCH', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->patch('uri', ['test' => 'param'])->shouldReturn($mockedResponse);
    }

    public function it_should_allow_a_head_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('HEAD', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->head('uri')->shouldReturn($mockedResponse);
    }

    public function it_should_allow_a_delete_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('DELETE', Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->delete('delete')->shouldReturn($mockedResponse);
    }

    public function it_allows_access_to_the_guzzle_client(
        ClientInterface $mockedClient)
    {
        $this->getClient()->shouldReturn($mockedClient);
    }

    public function it_should_fire_a_response_event(
        ClientInterface $mockedClient,
        EventInterface $mockedEvent,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedClient->send(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedEvent->fire('forrest.response', Argument::any())->shouldBeCalled();

        $this->versions();
    }
}
