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

class UserPasswordSpec extends ObjectBehavior
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

    protected $authenticationJSON = '{
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
        'authenticationFlow' => 'UserPassword',
        'credentials' => [
            'consumerKey'    => 'testingClientId',
            'consumerSecret' => 'testingClientSecret',
            'callbackURI'    => 'callbackURL',
            'loginURL'       => 'https://login.salesforce.com',
            'username'       => 'user@email.com',
            'password'       => 'mypassword',
        ],
        'parameters' => [
            'display'   => 'popup',
            'immediate' => 'false',
            'state'     => '',
            'scope'     => '',
        ],
        'instanceURL' => '',
        'authRedirect' => 'redirectURL',
        'version' => '30.0',
        'defaults' => [
            'method'          => 'get',
            'format'          => 'json',
            'compression'     => false,
            'compressionType' => 'gzip',
        ],
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
        RepositoryInterface $mockedRefreshTokenRepo,
        ResourceRepositoryInterface $mockedResourceRepo,
        RepositoryInterface $mockedStateRepo,
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

        $mockedResourceRepo->get(Argument::any())->willReturn('/services/data/v30.0/resource');
        $mockedResourceRepo->put(Argument::any())->willReturn(null);

        $mockedTokenRepo->get()->willReturn($this->token);
        $mockedTokenRepo->put($this->token)->willReturn(null);

        $mockedFormatter->setBody(Argument::any())->willReturn(null);
        $mockedFormatter->setHeaders()->willReturn([
            'Authorization' => 'Oauth accessToken',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ]);

        $mockedVersionRepo->get()->willReturn(['url' => '/resources']);

        $mockedFormatter->formatResponse($mockedResponse)->willReturn(['foo' => 'bar']);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\UserPassword');
    }

    public function it_should_authenticate(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        ResponseInterface $mockedVersionRepo,
        FormatterInterface $mockedFormatter,
        ResponseInterface $versionResponse)
    {
        $mockedHttpClient->request(
            'post',
            'url/services/oauth2/token',
            ['form_params' => [
                'grant_type'    => 'password',
                'client_id'     => 'testingClientId',
                'client_secret' => 'testingClientSecret',
                'username'      => 'user@email.com',
                'password'      => 'mypassword'
            ]])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedHttpClient->request(
            'get',
            'https://instance.salesforce.com/resources',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationJSON);

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

        $mockedVersionRepo->has()->willReturn(false);
        $mockedVersionRepo->put(["label" => "Winter 16", "url" => "/services/data/v35.0", "version" => "35.0"])->shouldBeCalled();

        $this->authenticate('url')->shouldReturn(null);
    }

    public function it_should_refresh(ClientInterface $mockedHttpClient, ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->request(
            'post',
            'https://login.salesforce.com/services/oauth2/token',
            ['form_params' => [
                'grant_type'    => 'password',
                'client_id'     => 'testingClientId',
                'client_secret' => 'testingClientSecret',
                'username'      => 'user@email.com',
                'password'      => 'mypassword']])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationJSON);

        $this->refresh()->shouldReturn(null);
    }

    public function it_should_return_the_request(
        ClientInterface $mockedHttpClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->request(
            'get',
            'url',
            ['headers' => [
                'Authorization' => 'Oauth accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json']])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $this->request('url', ['key' => 'value'])->shouldReturn(['foo' => 'bar']);
    }

    public function it_should_refresh_the_token_if_token_expired_exception_is_thrown(
        ClientInterface $mockedHttpClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $failedRequest = new Request('GET', 'fakeurl');
        $failedResponse = new Response(401);
        $requestException = new RequestException('Salesforce token has expired', $failedRequest, $failedResponse);

        //First request throws an exception
        $mockedHttpClient->request('get', 'url', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled(1)->willThrow($requestException);

        //Authenticates with refresh method
        $mockedHttpClient->request('post', 'https://login.salesforce.com/services/oauth2/token', ['form_params' => ['grant_type' => 'password', 'client_id' => 'testingClientId', 'client_secret' => 'testingClientSecret', 'username' => 'user@email.com', 'password' => 'mypassword']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled(1)->willReturn($this->authenticationJSON);

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Since we are calling the send() method twice, the behavior is correct for it to throw an exception. Actual behavior would never throw the exception, it would return a response.
        $tokenException = new TokenExpiredException(
            'Salesforce token has expired',
            $requestException);

        //Here we will handle a 401 exception and convert it to a TokenExpiredException
        $this->shouldThrow($tokenException)->duringRequest('url', ['key' => 'value']);
    }

    public function it_should_revoke_the_authentication_token(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->request(
            'post',
            'https://login.salesforce.com/services/oauth2/revoke',
            [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'token' => $this->token
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);
        $this->revoke()->shouldReturn($mockedResponse);
    }

    /*
     *
     * Specs below are for the parent class.
     *
     */

    public function it_should_return_all_versions(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get', 'https://instance.salesforce.com/services/data', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $versionArray = json_decode($this->versionJSON, true);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($versionArray);

        $this->versions()->shouldReturn($versionArray);
    }

    public function it_should_return_resources(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get', 'https://instance.salesforce.com/resources', ['headers' => ['Authorization' => 'Oauth accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->resources()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_identity(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->identity()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_limits(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/resources/limits',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->limits()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_describe(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/resources/sobjects',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->describe()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_query(
       ClientInterface $mockedHttpClient,
       ResponseInterface $mockedResponse,
       FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource?q=query',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->query('query')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_query_next(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/next',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->next('/next')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_queryExplain(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource?explain=queryExplain',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->queryExplain('queryExplain')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_queryAll(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource?q=queryAll',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->queryAll('queryAll')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_quick_actions(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->quickActions()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_search(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource?q=search',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->search('search')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_ScopeOrder(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource/scopeOrder',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->scopeOrder()->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_search_layouts(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource/layout/?q=objectList',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->searchLayouts('objectList')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_suggested_articles(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource/suggestTitleMatches?q=suggestedArticles',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->suggestedArticles('suggestedArticles')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_suggested_queries(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/data/v30.0/resource/suggestSearchQueries?q=suggested',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->suggestedQueries('suggested')->shouldReturn($this->decodedResponse);
    }

    public function it_should_return_custom_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'https://instance.salesforce.com/services/apexrest/FieldCase?foo=bar',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->custom('/FieldCase', ['parameters' => ['foo' => 'bar']])
            ->shouldReturn($this->decodedResponse);
    }

    public function it_returns_a_json_resource(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'uri',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->request('uri', [])->shouldReturn($this->decodedResponse);
    }

    public function it_returns_a_xml_resource(
        ClientInterface $mockedHttpClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient
            ->request('get',
                'uri',
                Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $decodedXML = simplexml_load_string($this->responseXML);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($decodedXML);

        $this->request('uri', ['format' => 'xml'])->shouldReturnAnInstanceOf('SimpleXMLElement');
    }

    public function it_should_format_header(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get',
            'uri',
            [
                'headers' => [
                    'Authorization' => 'Oauth accessToken',
                    'Accept'        => 'application/json',
                    'Content-Type'  => 'application/json',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->request('uri', ['compression' => false])->shouldReturn($this->decodedResponse);
    }

    public function it_should_format_header_in_url_encoding(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get',
            'uri',
            [
                'headers' => [
                    'Authorization' => 'bearer accesstoken',
                    'Accept'        => 'application/x-www-form-urlencoded',
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization' => 'bearer accesstoken',
            'Accept'        => 'application/x-www-form-urlencoded',
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ]);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn('rawresponse');

        $this->request('uri', ['format' => 'urlencoded'])->shouldReturn('rawresponse');
    }

    public function it_should_format_header_with_gzip(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get',
            'uri',
            [
                'headers' => [
                    'Authorization'    => 'bearer accesstoken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'gzip',
                    'Content-Encoding' => 'gzip',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);


        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization'    => 'bearer accesstoken',
            'Accept'           => 'application/json',
            'Content-Type'     => 'application/json',
            'Accept-Encoding'  => 'gzip',
            'Content-Encoding' => 'gzip',
        ]);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->request('uri', ['compression' => true, 'compressionType' => 'gzip'])->shouldReturn($this->decodedResponse);
    }

    public function it_should_format_header_with_deflate(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get',
            'uri',
            [
                'headers' => [
                    'Authorization'    => 'bearer accesstoken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'deflate',
                    'Content-Encoding' => 'deflate',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization'    => 'bearer accesstoken',
            'Accept'           => 'application/json',
            'Content-Type'     => 'application/json',
            'Accept-Encoding'  => 'deflate',
            'Content-Encoding' => 'deflate',
        ]);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->request('uri', ['compression' => true, 'compressionType' => 'deflate'])->shouldReturn($this->decodedResponse);
    }

    public function it_allows_access_to_the_guzzle_client(ClientInterface $mockedHttpClient)
    {
        $this->getClient()->shouldReturn($mockedHttpClient);
    }

    public function it_should_allow_a_get_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('GET', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);
        $this->get('uri')->shouldReturn($this->decodedResponse);
    }

    public function it_should_allow_a_post_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('POST', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);
        $this->post('uri', ['test' => 'param'])->shouldReturn($this->decodedResponse);
    }

    public function it_should_allow_a_put_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('PUT', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);
        $this->put('uri', ['test' => 'param'])->shouldReturn($this->decodedResponse);
    }

    public function it_should_allow_a_patch_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('PATCH', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);
        $this->patch('uri', ['test' => 'param'])->shouldReturn($this->decodedResponse);
    }

    public function it_should_allow_a_head_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter
    ) {
        $mockedHttpClient->request('HEAD', Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->head('url')->shouldReturn($this->decodedResponse);
    }

    public function it_should_allow_a_delete_request(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter
    ) {
        $mockedHttpClient->request('DELETE', Argument::any(), Argument::any())->willReturn($mockedResponse);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($this->decodedResponse);

        $this->delete('url')->shouldReturn($this->decodedResponse);
    }

    public function it_should_fire_a_response_event(
        ClientInterface $mockedHttpClient,
        EventInterface $mockedEvent,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter
    ) {
        $mockedHttpClient->request(Argument::any(), Argument::any(), Argument::any())->willReturn($mockedResponse);
        $mockedEvent->fire('forrest.response', Argument::any())->shouldBeCalled();

        $this->versions();
    }
}
