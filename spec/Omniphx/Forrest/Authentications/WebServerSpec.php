<?php

namespace spec\Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Omniphx\Forrest\Exceptions\TokenExpiredException;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\FormatterInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\ResourceRepositoryInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class WebServerSpec extends ObjectBehavior
{
    protected $versionJSON = '[
        {
            "label": "Spring 15",
            "url": "/services/data/v33.0",
            "version": "33.0"
        },
        {
            "label": "Summer 15",
            "url": "/services/data/v34.0",
            "version": "34.0"
        },
        {
            "label": "Winter 16",
            "url": "/services/data/v35.0",
            "version": "35.0"
        }
    ]';

    protected $versionArray = [
        [
            "label"   => "Spring 15",
            "url"     => "/services/data/v33.0",
            "version" => "33.0"
        ],
        [
            "label"   => "Summer 15",
            "url"     => "/services/data/v34.0",
            "version" => "34.0"
        ],
        [
            "label"   => "Winter 16",
            "url"     => "/services/data/v35.0",
            "version" => "35.0"
        ]
    ];

    protected $tokenJSON = '{
        "access_token": "00Do0000000secret",
        "id": "https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx",
        "instance_url": "https://na17.salesforce.com",
        "issued_at": "1447000236011",
        "signature": "secretsig",
        "token_type": "Bearer"
    }';

    protected $responseXML = '
        <meseek>
            <intro>I\'m Mr. Meseeks, look at me!</intro>
            <problem>Get 2 strokes off Gary\'s golf swing</problem>
            <solution>Have you tried squring your shoulders, Gary?</solution>
        </meseek>';

    protected $token = [
        'access_token' => '00Do0000000secret',
        'instance_url' => 'https://na17.salesforce.com',
        'id'           => 'https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx',
        'token_type'   => 'Bearer',
        'issued_at'    => '1447000236011',
        'signature'    => 'secretsig'];

    protected $decodedResponse = ['foo' => 'bar'];

    protected $settings = [
        'authentication' => 'WebServer',
        'credentials' => [
            'consumerKey'    => 'testingClientId',
            'consumerSecret' => 'testingClientSecret',
            'callbackURI'    => 'callbackURL',
            'loginURL'       => 'https://login.salesforce.com',
        ],
        'parameters' => [
            'display'   => '',
            'immediate' => false,
            'state'     => '',
            'scope'     => '',
            'prompt'    => '',
        ],
        'defaults' => [
            'method'          => 'get',
            'format'          => 'json',
            'compression'     => false,
            'compressionType' => 'gzip',
        ],
        'storage' => [
            'type'          => 'session',
            'path'          => 'forrest_',
            'expire_in'     => 60,
            'store_forever' => false,
        ],
        'version' => '',
        'instanceURL' => '',
        'language' => 'en_US',
    ];

    public function let(
        ClientInterface $mockedHttpClient,
        EncryptorInterface $mockedEncryptor,
        EventInterface $mockedEvent,
        InputInterface $mockedInput,
        RedirectInterface $mockedRedirect,
        ResponseInterface $mockedResponse,
        RepositoryInterface $mockedInstanceURLRepo,
        ResourceRepositoryInterface $mockedResourceRepo,
        RepositoryInterface $mockedStateRepo,
        RepositoryInterface $mockedRefreshTokenRepo,
        RepositoryInterface $mockedTokenRepo,
        RepositoryInterface $mockedVersionRepo,
        FormatterInterface $mockedFormatter)
    {
        $this->beConstructedWith(
            $mockedHttpClient,
            $mockedEncryptor,
            $mockedEvent,
            $mockedInput,
            $mockedRedirect,
            $mockedInstanceURLRepo,
            $mockedRefreshTokenRepo,
            $mockedResourceRepo,
            $mockedStateRepo,
            $mockedTokenRepo,
            $mockedVersionRepo,
            $mockedFormatter,
            $this->settings);

        $mockedInstanceURLRepo->get()->willReturn('https://instance.salesforce.com');
        $mockedRefreshTokenRepo->get()->willReturn('refreshToken');

        $mockedFormatter->setHeaders()->willReturn([
            'Authorization' => 'Oauth accessToken',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ]);

        $mockedFormatter->formatResponse($mockedResponse)->willReturn(['foo' => 'bar']);

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
        ClientInterface $mockedHttpClient,
        InputInterface $mockedInput,
        RepositoryInterface $mockedInstanceURLRepo,
        RepositoryInterface $mockedTokenRepo,
        ResourceRepositoryInterface $mockedResourceRepo,
        ResponseInterface $mockedResponse,
        ResponseInterface $resourceResponse,
        ResponseInterface $versionResponse,
        ResponseInterface $tokenResponse,
        ResponseInterface $mockedVersionRepo,
        FormatterInterface $mockedFormatter)
    {
        $mockedInput->get('code')->shouldBeCalled()->willReturn('callbackCode');
        $stateOptions = ['loginUrl' => 'https://login.salesforce.com'];
        $mockedInput->get('state')->shouldBeCalled()->willReturn(urlencode(json_encode($stateOptions)));

        $mockedHttpClient->request(
            'post',
            'https://login.salesforce.com/services/oauth2/token',
            ['form_params' => [
                'code'          => 'callbackCode',
                'grant_type'    => 'authorization_code',
                'client_id'     => 'testingClientId',
                'client_secret' => 'testingClientSecret',
                'redirect_uri'  => 'callbackURL'
            ]])
            ->shouldBeCalled()
            ->willReturn($tokenResponse);

        $tokenResponse->getBody()->shouldBeCalled()->willReturn($this->tokenJSON);
        $mockedTokenRepo->put($this->token)->shouldBeCalled();
        $mockedVersionRepo->put(["label" => "Winter 16", "url" => "/services/data/v35.0", "version" => "35.0"])->shouldBeCalled();
        $mockedInstanceURLRepo->get()->shouldBeCalled()->willReturn('https://instance.salesforce.com');
 
        $mockedHttpClient->request(
            'get',
            'https://instance.salesforce.com/services/data',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ]])
            ->shouldBeCalled()
            ->willReturn($versionResponse);

        $mockedFormatter->formatResponse($versionResponse)->shouldBeCalled()->willReturn($this->versionArray);

        $mockedVersionRepo->get()->shouldBeCalled()->willReturn(['url' => '/services/data/v35.0']);

        $mockedHttpClient->request(
            'get',
            'https://instance.salesforce.com/services/data/v35.0',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json'
            ]])
            ->shouldBeCalled()
            ->willReturn($resourceResponse);

        $mockedFormatter->formatResponse($resourceResponse)->shouldBeCalled()->willReturn('resources');

        $this->callback()->shouldReturn($stateOptions);
    }

    public function it_should_refresh(
        ClientInterface $mockedHttpClient,
        EncryptorInterface $mockedEncryptor,
        RepositoryInterface $mockedTokenRepo,
        ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->request(
            'post',
            'https://instance.salesforce.com/services/oauth2/token',
            ['form_params'=> [
                'refresh_token' => 'refreshToken',
                'grant_type' => 'refresh_token',
                'client_id' => 'testingClientId',
                'client_secret' => 'testingClientSecret'
            ]])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->willReturn($this->tokenJSON);

        $mockedTokenRepo->put($this->token)->shouldBeCalled();

        $this->refresh('token')->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedHttpClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->send($mockedRequest)->willReturn($mockedResponse);
        $mockedHttpClient->request(
            'get',
            'url',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json']])
            ->willReturn($mockedResponse);

        $this->request('url', ['key' => 'value'])->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_refresh_the_token_if_response_throws_error(
        ClientInterface $mockedHttpClient,
        FormatterInterface $mockedFormatter,
        RepositoryInterface $mockedTokenRepo,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        //Testing that we catch 401 errors and refresh the salesforce token.
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedHttpClient->request(
            'get',
            'url',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json']])
            ->shouldBeCalled()
            ->willThrow($requestException);

        $mockedHttpClient->request(
            'post',
            'https://instance.salesforce.com/services/oauth2/token',
            ['form_params'=> [
                'refresh_token' => 'refreshToken',
                'grant_type' => 'refresh_token',
                'client_id' => 'testingClientId',
                'client_secret' => 'testingClientSecret'
            ]])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->tokenJSON);

        $mockedTokenRepo->put($this->token)->shouldBeCalled();

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Basically creating an infinite loop of the token being expired. What we can do is verify the methods in the refresh() method are being fired.
        $tokenException = new TokenExpiredException('Salesforce token has expired', $requestException);

        $this->shouldThrow($tokenException)->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_not_call_refresh_method_if_there_is_no_token(
        ClientInterface $mockedHttpClient,
        RequestInterface $failedRequest,
        RepositoryInterface $mockedRefreshTokenRepo)
    {
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedHttpClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willThrow($requestException);

        $mockedRefreshTokenRepo->get()->willThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException');

        $this->shouldThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException')->duringRequest('url', ['key' => 'value']);
    }
}
