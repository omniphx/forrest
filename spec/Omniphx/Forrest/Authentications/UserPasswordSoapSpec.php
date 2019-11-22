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

class UserPasswordSoapSpec extends ObjectBehavior
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

    protected $token = [
        "signature" => "SOAPHasNoSecretSig",
        "id" => "https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx",
        "access_token" => "00Do0000000secret",
        "token_type" => "Bearer",
        "instance_url" => "https://instance.salesforce.com"
        ];

    protected $authenticationJSON = [
        "signature" => "SOAPHasNoSecretSig",
        "id" => "https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx",
        "access_token" => "00Do0000000secret",
        "token_type" => "Bearer",
        "instance_url" => "https://instance.salesforce.com"
        ];

    protected $failedAuthenticationXML = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:sf="urn:fault.partner.soap.sforce.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soapenv:Body><soapenv:Fault><faultcode>sf:INVALID_LOGIN</faultcode><faultstring>INVALID_LOGIN: Invalid username, password, security token; or user locked out.</faultstring><detail><sf:LoginFault xsi:type="sf:LoginFault"><sf:exceptionCode>INVALID_LOGIN</sf:exceptionCode><sf:exceptionMessage>Invalid username, password, security token; or user locked out.</sf:exceptionMessage></sf:LoginFault></detail></soapenv:Fault></soapenv:Body></soapenv:Envelope>';

    protected $authenticationXML = '<?xml version="1.0" encoding="UTF-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns="urn:partner.soap.sforce.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><soapenv:Body><loginResponse><result><metadataServerUrl>https://instance.salesforce.com/services/Soap/m/46.0/00000000000uXgY</metadataServerUrl><passwordExpired>false</passwordExpired><sandbox>false</sandbox><serverUrl>https://instance.salesforce.com/services/Soap/u/46.0/00D36000000uXgY</serverUrl><sessionId>00Do0000000secret</sessionId><userId>005o0000000xxxxx</userId><userInfo><accessibilityMode>false</accessibilityMode><currencySymbol>$</currencySymbol><orgAttachmentFileSizeLimit>5242880</orgAttachmentFileSizeLimit><orgDefaultCurrencyIsoCode>USD</orgDefaultCurrencyIsoCode><orgDisallowHtmlAttachments>false</orgDisallowHtmlAttachments><orgHasPersonAccounts>false</orgHasPersonAccounts><organizationId>00Do0000000xxxxx</organizationId><organizationMultiCurrency>false</organizationMultiCurrency><organizationName>The Organization Name</organizationName><profileId>00000000000000CAA4</profileId><roleId>00E36000000000000C</roleId><sessionSecondsValid>7200</sessionSecondsValid><userDefaultCurrencyIsoCode xsi:nil="true"/><userEmail>user@email.com</userEmail><userFullName>John Doe</userFullName><userId>005o0000000xxxxx</userId><userLanguage>en_US</userLanguage><userLocale>en_US</userLocale><userName>user@email.com</userName><userTimeZone>Pacific/Honolulu</userTimeZone><userType>Standard</userType><userUiSkin>Theme3</userUiSkin></userInfo></result></loginResponse></soapenv:Body></soapenv:Envelope>';

    protected $responseXML = '
        <meseek>
            <intro>I\'m Mr. Meseeks, look at me!</intro>
            <problem>Get 2 strokes off Gary\'s golf swing</problem>
            <solution>Have you tried squring your shoulders, Gary?</solution>
        </meseek>';

    protected $decodedResponse = ['foo' => 'bar'];

    protected $settings = [
        'authenticationFlow' => 'UserPasswordSoap',
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
            'Authorization' => 'Bearer accessToken',
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ]);
        $mockedFormatter->getDefaultMIMEType()->willReturn('application/json');
        $mockedVersionRepo->get()->willReturn(['url' => '/resources']);
        $mockedFormatter->formatResponse($mockedResponse)->willReturn(['foo' => 'bar']);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\UserPasswordSoap');
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
            'url/services/Soap/u/46.0',
            ["http_errors" => false,
                "headers" =>
                    ["Content-Type" => "text/xml; charset=UTF-8",
                    "SOAPAction" => "login"],
                "body" => '<?xml version="1.0" encoding="utf-8" ?><env:Envelope xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://schemas.xmlsoap.org/soap/envelope/"><env:Body><n1:login xmlns:n1="urn:partner.soap.sforce.com"><n1:username>user@email.com</n1:username><n1:password>mypassword</n1:password></n1:login></env:Body></env:Envelope>'])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedHttpClient->request(
            'get',
            'https://instance.salesforce.com/resources',
            ['headers' => [
                'Authorization' => 'Bearer accessToken',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->getBody()->shouldBeCalled()->willReturn($this->authenticationXML);

        $mockedHttpClient->request(
            'get',
            'https://instance.salesforce.com/services/data',
            ['headers' => [
                'Authorization' => 'Bearer accessToken',
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

    public function it_should_return_the_request(
        ClientInterface $mockedHttpClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedHttpClient->request(
            'get',
            'url',
            ['headers' => [
                'Authorization' => 'Bearer accessToken',
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json']])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $this->request('url', ['key' => 'value'])->shouldReturn(['foo' => 'bar']);
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
        $mockedHttpClient->request('get', 'https://instance.salesforce.com/services/data', ['headers' => ['Authorization' => 'Bearer accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

        $versionArray = json_decode($this->versionJSON, true);

        $mockedFormatter->formatResponse($mockedResponse)->shouldBeCalled()->willReturn($versionArray);

        $this->versions()->shouldReturn($versionArray);
    }

    public function it_should_return_resources(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        FormatterInterface $mockedFormatter)
    {
        $mockedHttpClient->request('get', 'https://instance.salesforce.com/resources', ['headers' => ['Authorization' => 'Bearer accessToken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])->shouldBeCalled()->willReturn($mockedResponse);

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
                    'Authorization' => 'Bearer accessToken',
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
                    'Authorization' => 'bearer accessToken',
                    'Accept'        => 'application/x-www-form-urlencoded',
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization' => 'bearer accessToken',
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
                    'Authorization'    => 'bearer accessToken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'gzip',
                    'Content-Encoding' => 'gzip',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);


        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization'    => 'bearer accessToken',
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
                    'Authorization'    => 'bearer accessToken',
                    'Accept'           => 'application/json',
                    'Content-Type'     => 'application/json',
                    'Accept-Encoding'  => 'deflate',
                    'Content-Encoding' => 'deflate',
                ]
            ])
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedFormatter->setHeaders()->shouldBeCalled()->willReturn([
            'Authorization'    => 'bearer accessToken',
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
