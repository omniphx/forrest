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

class WebServerSpec extends ObjectBehavior
{
    protected $versionJSON = '[{"label":"Winter \'16","url":"/services/data/v35.0","version":"35.0"}]';

    protected $authenticationJSON = '{"access_token":"00Do0000000secret","instance_url":"https://na17.salesforce.com","id":"https://login.salesforce.com/id/00D","token_type":"Bearer","issued_at":"1447000236011","signature":"secretsig","refresh_token":"refreshToken"}';

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
            'credentials'        => [
                'consumerKey'    => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'    => 'callbackURL',
                'loginURL'       => 'https://login.salesforce.com',
            ],
            'authenticationFlow' => 'WebServer',
            'parameters'         => [
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',
                'prompt'    => '',
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

        $token = [
            'access_token'  => 'xxxxaccess.tokenxxxx',
            'id'            => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'instance_url'  => 'https://na##.salesforce.com',
            'token_type'    => 'Bearer',
            'refresh_token' => 'xxxxrefresh.tokenxxxx',
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
            'appMenu'      => '/services/data/v30.0/appMenu',
        ];

        //Storage stubs
        $mockedStorage->get('resources')->willReturn($resources);
        $mockedStorage->get('version')->willReturn([
            'url'     => '/services/data/v35.0',
            'version' => '35.0', ]);
        $mockedStorage->getTokenData()->willReturn([
            'access_token' => 'accessToken',
            'id'           => 'https://login.salesforce.com/id/00D',
            'instance_url' => 'https://na00.salesforce.com',
            'token_type'   => 'Oauth',
        ]);

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
        $this->shouldHaveType('Omniphx\Forrest\Authentications\WebServer');
    }

    public function it_should_authenticate(
        RedirectInterface $mockedRedirect)
    {
        $mockedRedirect->to(Argument::any())->willReturn('redirectURL');
        $this->authenticate()->shouldReturn('redirectURL');
    }

    public function it_should_callback(
        ClientInterface $mockedClient,
        InputInterface $mockedInput,
        ResponseInterface $tokenResponse,
        ResponseInterface $storeResources,
        StorageInterface $mockedStorage)
    {
        $mockedInput->get('code')->shouldBeCalled()->willReturn('callbackCode');
		$stateOptions = ['loginUrl' => 'https://login.salesforce.com'];
        $mockedInput->get('state')->shouldBeCalled()->willReturn(urlencode(json_encode($stateOptions)));

        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/token', ['form_params' => ['code' => 'callbackCode', 'grant_type' => 'authorization_code', 'client_id' => 'testingClientId', 'client_secret' => 'testingClientSecret', 'redirect_uri' => 'callbackURL']])->shouldBeCalled()->willReturn($tokenResponse);
        $tokenResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationJSON);
        $mockedClient->request('get', 'https://na00.salesforce.com', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($storeResources);
        $mockedStorage->get('version')->willReturn(null);
        $mockedStorage->put('loginURL', 'https://login.salesforce.com')->shouldBeCalled();
        $mockedStorage->put('resources', ['foo' => 'bar'])->shouldBeCalled();
		$mockedStorage->put('stateOptions', $stateOptions)->shouldBeCalled();
        $mockedStorage->putTokenData(['access_token' => '00Do0000000secret', 'instance_url' => 'https://na17.salesforce.com', 'id' => 'https://login.salesforce.com/id/00D', 'token_type' => 'Bearer', 'issued_at' => '1447000236011', 'signature' => 'secretsig', 'refresh_token' => 'refreshToken'])->shouldBeCalled();
        $mockedStorage->putRefreshToken('refreshToken')->shouldBeCalled();

        $storeResources->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $this->callback()->shouldReturn($stateOptions);
    }

    public function it_should_refresh(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        StorageInterface $mockedStorage
    ) {
        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');
        $mockedStorage->get('loginURL')->shouldBeCalled()->willReturn('https://login.salesforce.com');

        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->willReturn($this->authenticationJSON);

        $mockedStorage->putTokenData(Argument::type('array'))->shouldBeCalled();

        $this->refresh('token')->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse
    ) {
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);
        $mockedClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $this->request('url', ['key' => 'value'])->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_refresh_the_token_if_response_throws_error(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse
    ) {
        //Testing that we catch 401 errors and refresh the salesforce token.
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willThrow($requestException);

        $mockedStorage->get('loginURL')->shouldBeCalled()->willReturn('https://login.salesforce.com');
        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');

        $mockedClient->request('post', 'https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->responseJSON);

        $mockedStorage->putTokenData(Argument::type('array'))->shouldBeCalled();

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Basically creating an infinite loop of the token being expired. What we can do is verify the methods in the refresh() method are being fired.
        $tokenException = new TokenExpiredException(
            'Salesforce token has expired',
            $requestException);

        $this->shouldThrow($tokenException)->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_not_call_refresh_method_if_there_is_no_token(
        ClientInterface $mockedClient,
        RequestInterface $failedRequest,
        StorageInterface $mockedStorage
    ) {
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willThrow($requestException);

        $mockedStorage->getRefreshToken()->willThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException');

        $this->shouldThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException')->duringRequest('url', ['key' => 'value']);
    }
}
