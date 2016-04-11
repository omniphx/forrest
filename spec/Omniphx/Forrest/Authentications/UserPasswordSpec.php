<?php

namespace spec\Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class UserPasswordSpec extends ObjectBehavior
{
    protected $versionJSON = "[{\"label\":\"Winter '11\",\"url\":\"\/services\/data\/v20.0\",\"version\":\"20.0\"},{\"label\":\"Spring '11\",\"url\":\"\/services\/data\/v21.0\",\"version\":\"21.0\"},{\"label\":\"Summer '11\",\"url\":\"\/services\/data\/v22.0\",\"version\":\"22.0\"},{\"label\":\"Winter '12\",\"url\":\"\/services\/data\/v23.0\",\"version\":\"23.0\"},{\"label\":\"Spring '12\",\"url\":\"\/services\/data\/v24.0\",\"version\":\"24.0\"},{\"label\":\"Summer '12\",\"url\":\"\/services\/data\/v25.0\",\"version\":\"25.0\"},{\"label\":\"Winter '13\",\"url\":\"\/services\/data\/v26.0\",\"version\":\"26.0\"},{\"label\":\"Spring '13\",\"url\":\"\/services\/data\/v27.0\",\"version\":\"27.0\"},{\"label\":\"Summer '13\",\"url\":\"\/services\/data\/v28.0\",\"version\":\"28.0\"},{\"label\":\"Winter '14\",\"url\":\"\/services\/data\/v29.0\",\"version\":\"29.0\"},{\"label\":\"Spring '14\",\"url\":\"\/services\/data\/v30.0\",\"version\":\"30.0\"},{\"label\":\"Summer '14\",\"url\":\"\/services\/data\/v31.0\",\"version\":\"31.0\"},{\"label\":\"Winter '15\",\"url\":\"\/services\/data\/v32.0\",\"version\":\"32.0\"},{\"label\":\"Spring '15\",\"url\":\"\/services\/data\/v33.0\",\"version\":\"33.0\"},{\"label\":\"Summer '15\",\"url\":\"\/services\/data\/v34.0\",\"version\":\"34.0\"},{\"label\":\"Winter '16\",\"url\":\"\/services\/data\/v35.0\",\"version\":\"35.0\"}]";

    protected $authenticationJSON = "{\"access_token\":\"00Do0000000secret\",\"instance_url\":\"https:\/\/na17.salesforce.com\",\"id\":\"https:\/\/login.salesforce.com\/id\/00Do0000000xxxxx\/005o0000000xxxxx\",\"token_type\":\"Bearer\",\"issued_at\":\"1447000236011\",\"signature\":\"secretsig\"}";

    protected $responseJSON = '{"foo":"bar"}';

    protected $responseXML = "<meseek><intro>I'm Mr. Meseeks, look at me!</intro><role>Get 2 strokes off Gary's golf swing</role><solution>Has he tried keeping his shoulder's square?</solution></meseek>";

    public function let(
        ClientInterface $mockedClient,
        EventInterface $mockedEvent,
        InputInterface $mockedInput,
        RedirectInterface $mockedRedirect,
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage
    ) {
        $settings = [
            'authenticationFlow' => 'UserPassword',
            'credentials'        => [
                'consumerKey'    => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'    => 'callbackURL',
                'loginURL'       => 'https://login.salesforce.com',
                'username'       => 'user@email.com',
                'password'       => 'mypassword',

            ],
            'parameters'         => [
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',
            ],
            'instanceURL'        => '',
            'authRedirect'       => 'redirectURL',
            'version'            => '30.0',
            'defaults'           => [
                'method'          => 'get',
                'format'          => 'json',
                'compression'     => false,
                'compressionType' => 'gzip',
            ],
            'language'           => 'en_US',
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
            'appMenu'      => '/services/data/v30.0/appMenu',
        ]);
        $mockedStorage->get('version')->willReturn([
            'url' => 'resourceURLs',
        ]);
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'instance_url' => 'https://na00.salesforce.com',
            'token_type'   => 'Oauth',
        ]);
        $mockedStorage->putTokenData(Argument::any())->willReturn(null);
        $mockedStorage->put(Argument::any(), Argument::any())->willReturn(null);

        $this->beConstructedWith(
            $mockedClient,
            $mockedEvent,
            $mockedInput,
            $mockedRedirect,
            $mockedStorage,
            $settings);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\UserPassword');
    }

    public function it_should_authenticate(
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage)
    {
        $mockedClient->request('post', 'url/services/oauth2/token', ['form_params' => ['grant_type' => 'password', 'client_id' => 'testingClientId', 'client_secret' => 'testingClientSecret', 'username' => 'user@email.com', 'password' => 'mypassword']])->shouldBeCalled(1)->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationJSON);
        $mockedStorage->putTokenData(Argument::any())->shouldBeCalled(1);
        $mockedClient->request('get', 'https://na00.salesforce.comresourceURLs', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willReturn($mockedResponse);

        $this->authenticate('url')->shouldReturn(null);
    }

    public function it_should_refresh(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        StorageInterface $mockedStorage)
    {
        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/token', ['form_params' => ['grant_type' => 'password', 'client_id' => 'testingClientId', 'client_secret' => 'testingClientSecret', 'username' => 'user@email.com', 'password' => 'mypassword']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled(1)->willReturn($this->authenticationJSON);

        $this->refresh()->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);

        $mockedClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled(1)->willReturn($this->responseJSON);

        $this->request('url', ['key' => 'value'])->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_refresh_the_token_if_token_expired_exception_is_thrown(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willThrow($requestException);

        //Authenticates with refresh method
        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/token', ['form_params' => ['grant_type' => 'password', 'client_id' => 'testingClientId', 'client_secret' => 'testingClientSecret', 'username' => 'user@email.com', 'password' => 'mypassword']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled(1)->willReturn($this->authenticationJSON);

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Since we are calling the send() method twice, the behavior is correct for it to throw an exception. Actual behavior would never throw the exception, it would return a response.
        $tokenException = new TokenExpiredException(
            'Salesforce token has expired',
            $requestException);

        //Here we will handle a 401 exception and convert it to a TokenExpiredException
        $this->shouldThrow($tokenException)->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_revoke_the_authentication_token(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/revoke', ['headers' => ['content-type' => 'application/x-www-form-urlencoded'], 'form_params' => ['token' => 'accessToken']])->shouldBeCalled()->willReturn($mockedResponse);
        $this->revoke()->shouldReturn($mockedResponse);
    }

    /*
     *
     * Specs below are for the parent class.
     *
     */

    public function it_should_return_the_versions(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->versionJSON);

        $versionArray = json_decode($this->versionJSON, true);

        $this->versions()->shouldReturn($versionArray);
        // $mockedResponse->json()->shouldBeCalled()->willReturn(['version' => '29.0', 'version' => '30.0']);

        // $this->versions()->shouldReturn(['version' => '29.0', 'version' => '30.0']);
    }

    public function it_should_return_resources(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.comresourceURLs', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->resources()->shouldReturn($responseJSON);
    }

    public function it_should_return_identity(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->identity()->shouldReturn($responseJSON);
    }

    public function it_should_return_limits(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);

        $mockedClient->request('get', 'https://na00.salesforce.comversionURL/limits', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->limits()->shouldReturn($responseJSON);
    }

    public function it_should_return_describe(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(['url' => 'versionURL']);
        $mockedClient->request('get', 'https://na00.salesforce.comversionURL/sobjects', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->describe()->shouldReturn($responseJSON);
    }

    public function it_should_return_query(
       ClientInterface $mockedClient,
       ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/query?q=query', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->query('query')->shouldReturn($responseJSON);
    }

    public function it_should_return_query_next(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.comnext', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->next('next')->shouldReturn($responseJSON);
    }

    public function it_should_return_queryExplain(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/query?explain=queryExplain', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->queryExplain('queryExplain')->shouldReturn($responseJSON);
    }

    public function it_should_return_queryAll(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/queryAll?q=queryAll', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->queryAll('queryAll')->shouldReturn($responseJSON);
    }

    public function it_should_return_quickActions(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/quickActions', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->quickActions()->shouldReturn($responseJSON);
    }

    public function it_should_return_search(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/search?q=search', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->search('search')->shouldReturn($responseJSON);
    }

    public function it_should_return_ScopeOrder(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/search/scopeOrder', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->scopeOrder()->shouldReturn($responseJSON);
    }

    public function it_should_return_searchLayouts(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/search/layout/?q=objectList', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->searchLayouts('objectList')->shouldReturn($responseJSON);
    }

    public function it_should_return_suggestedArticles(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/search/suggestTitleMatches?q=suggestedArticles', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->suggestedArticles('suggestedArticles')->shouldReturn($responseJSON);
    }

    public function it_should_return_suggestedQueries(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'https://na00.salesforce.com/services/data/v30.0/search/suggestSearchQueries?q=suggested', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->suggestedQueries('suggested')->shouldReturn($responseJSON);
    }

    public function it_should_return_custom_request(
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest
    ) {
        $mockedClient->request('get',
            'https://na00.salesforce.com/services/apexrest/FieldCase?foo=bar', [
                'headers' => [
                    'Authorization' => 'Oauth accessToken',
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ], ])->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->custom('/FieldCase', ['parameters' => ['foo' => 'bar']])
            ->shouldReturn($responseJSON);
    }

    public function it_returns_a_json_resource(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'uri', ['headers' => ['Authorization' => 'bearer abc', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'abc',
            'instance_url' => 'def',
            'token_type'   => 'bearer', ]);

        $this->request('uri', [])->shouldReturn($responseJSON);
    }

    public function it_returns_a_xml_resource(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->request('get', 'uri', ['headers' => ['Authorization' => 'bearer abc', 'Accept' => 'application/xml', 'Content-Type' => 'application/xml']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseXML);

        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'abc',
            'instance_url' => 'def',
            'token_type'   => 'bearer',
        ]);

        $this->request('uri', ['format' => 'xml'])->shouldReturnAnInstanceOf('SimpleXMLElement');
    }

    public function it_should_format_header_in_urlencoding(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer',
        ]);

        $mockedClient->request(
            'get',
            'uri',
            [
                'headers' => [
                    'Authorization' => 'bearer accesstoken',
                    'Accept'        => 'application/x-www-form-urlencoded',
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
            ])
            ->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn('rawresponse');

        $this->request('uri', ['format' => 'urlencoded'])->shouldReturn('rawresponse');
    }

    public function it_should_format_header_with_gzip(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer',
        ]);

        $mockedClient->request(
            'get',
            'uri',
            [
                'headers' => [
                    'Authorization'    => 'bearer accesstoken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'gzip',
                    'Content-Encoding' => 'gzip',
                ],
            ])
            ->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->request('uri', ['compression' => true, 'compressionType' => 'gzip'])->shouldReturn($responseJSON);
    }

    public function it_should_format_header_with_deflate(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer',
        ]);

        $mockedClient->request(
            'get',
            'uri',
            [
                'headers' => [
                    'Authorization'    => 'bearer accesstoken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'deflate',
                    'Content-Encoding' => 'deflate',
                ],
            ])
            ->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->request('uri', ['compression' => true, 'compressionType' => 'deflate'])->shouldReturn($responseJSON);
    }

    public function it_should_format_header_without_compression(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accesstoken',
            'instance_url' => 'def',
            'token_type'   => 'bearer',
        ]);

        $mockedClient->request(
            'get',
            'uri',
            [
                'headers' => [
                    'Authorization' => 'bearer accesstoken',
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ],
            ])
            ->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->request('uri', ['compression' => false])->shouldReturn($responseJSON);
    }

    public function it_allows_access_to_the_guzzle_client(
        ClientInterface $mockedClient
    ) {
        $this->getClient()->shouldReturn($mockedClient);
    }

    public function it_should_allow_a_get_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('GET', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);
        $this->get('uri')->shouldReturn($responseJSON);
    }

    public function it_should_allow_a_post_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('POST', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);
        $this->post('uri', ['test' => 'param'])->shouldReturn($responseJSON);
    }

    public function it_should_allow_a_put_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('PUT', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);
        $this->put('uri', ['test' => 'param'])->shouldReturn($responseJSON);
    }

    public function it_should_allow_a_patch_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('PATCH', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);
        $this->patch('uri', ['test' => 'param'])->shouldReturn($responseJSON);
    }

    public function it_should_allow_a_head_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('HEAD', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->head('url')->shouldReturn($responseJSON);
    }

    public function it_should_allow_a_delete_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request('DELETE', Argument::any(), Argument::any())->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $responseJSON = json_decode($this->responseJSON, true);

        $this->delete('url')->shouldReturn($responseJSON);
    }

    public function it_should_fire_a_response_event(
        ClientInterface $mockedClient,
        EventInterface $mockedEvent,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->request(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedRequest);
        $mockedClient->send(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedEvent->fire('forrest.response', Argument::any())->shouldBeCalled();

        $this->versions();
    }
}
