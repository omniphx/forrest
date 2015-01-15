<?php

namespace spec\Omniphx\Forrest\Authentications;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Message\RequestInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\InputInterface;

class WebServerSpec extends ObjectBehavior
{
    function let(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        RedirectInterface $mockedRedirect,
        InputInterface $mockedInput)
    {
        $settings  = array(
            'creditials' => array(
                'consumerKey'     => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'  => 'callbackURL',
                'loginURL'     => 'https://login.salesforce.com',
            ),
            'authenticationFlow' => 'WebServer',
            'parameters'     => array(
                'display'   => 'popup',
                'immediate' => 'false',
                'state'     => '',
                'scope'     => '',
                'prompt'    => '',
            ),
            'instanceURL' => '',
            'authRedirect' => 'redirectURL',
            'version' => '30.0',
            'defaults' => array(
                'method'          => 'get',
                'format'          => 'json',
                'compression'     => false,
                'compressionType' => 'gzip',
            ),
            'language' => 'en_US'
        );

        $token = [
            'access_token'  => 'xxxxaccess.tokenxxxx',
            'id'            => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'instance_url'  => 'https://na##.salesforce.com',
            'token_type'    => 'Bearer',
            'refresh_token' => 'xxxxrefresh.tokenxxxx'];

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
            'identity'     => 'https://login.salesforce.com/id/00Di0000000XXXXXX/005i0000000xxxxXXX',
            'flexiPage'    => '/services/data/v30.0/flexiPage',
            'search'       => '/services/data/v30.0/search',
            'quickActions' => '/services/data/v30.0/quickActions',
            'appMenu'      => '/services/data/v30.0/appMenu'];

        //Storage stubs
        $mockedStorage->get('resources')->willReturn($resources);
        $mockedStorage->get('version')->willReturn([
            'url' => '/resourceURL']);
        $mockedStorage->getToken()->willReturn($token);
        $mockedStorage->putToken(Argument::type('array'));

        //Client stubs
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $this->beConstructedWith(
            $mockedClient,
            $mockedStorage,
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
        ResponseInterface $tokenResponse,
        ResponseInterface $versionResponse,
        StorageInterface $mockedStorage)
    {
        $mockedClient->post('https://login.salesforce.com/services/oauth2/token',Argument::type('array'))->shouldBeCalled(1)->willReturn($tokenResponse);
        $mockedClient->send(Argument::any())->shouldBeCalled(1)->willReturn($versionResponse);
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);

        $tokenResponse->json()->shouldBeCalled(1)->willReturn(array(
            'access_token'  => 'value1',
            'refresh_token' => 'value2'));

        $versionResponse->json()->shouldBeCalled()->willReturn([['version'=>'30.0'],['version'=>'31.0']]);

        $mockedStorage->putToken(Argument::type('array'))->shouldBeCalled();
        $mockedStorage->putRefreshToken(Argument::exact('value2'))->shouldBeCalled();
        $mockedStorage->put(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->callback()->shouldReturn(null);
    }

    function it_should_refresh(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse,
        StorageInterface $mockedStorage)
    {
        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');

        $mockedClient->post('https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn(['key'=>'value']);

        $mockedStorage->putToken(Argument::type('array'))->shouldBeCalled();

        $this->refresh('token')->shouldReturn(null);

    }

    function it_should_return_the_request(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->send($mockedRequest)->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);

        $mockedResponse->json()->shouldBeCalled()->willReturn('worked');

        $this->request('url',['key'=>'value'])->shouldReturn('worked');
    }

    function it_should_refresh_the_token_if_response_throws_error(
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedClient->send($mockedRequest)->willThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException');

        $mockedStorage->getRefreshToken()->shouldBeCalled()->willReturn('refresh_token');

        $mockedClient->post('https://login.salesforce.com/services/oauth2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled(1)->willReturn(['key'=>'value']);

        $mockedStorage->putToken(Argument::type('array'))->shouldBeCalled();

        //This might seem counter-intuitive. We are throwing an exception with the send() method, but we can't stop it. Since we are calling the send() method twice, the behavior is correct for it to throw an exception. Actual behavior would never throw the exception, it would return a response.
        $this->shouldThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException')->duringRequest('url',['key'=>'value']);
    }

    function it_should_not_call_refresh_method_if_there_is_no_token(
        ClientInterface $mockedClient,
        RequestInterface $failedRequest,
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->send($failedRequest)->willThrow('\Omniphx\Forrest\Exceptions\TokenExpiredException');

        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($failedRequest);

        $mockedStorage->getRefreshToken()->willThrow('\Omniphx\Forrest\Exceptions\MissingRefreshTokenException');

        $this->shouldThrow('Omniphx\Forrest\Exceptions\MissingRefreshTokenException')->duringRequest('url',['key'=>'value']);
    }

    //Client class

    function it_should_revoke_the_authentication_token(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RedirectInterface $mockedRedirect,
        RequestInterface $mockedRequest)
    {
        $mockedClient->post(Argument::type('string'),Argument::type('array'))->shouldBeCalled();
        $this->revoke()->shouldReturn(null);
    }

    function it_should_return_the_versions(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn(array('version'=>'29.0','version'=>'30.0'));

        $this->versions()->shouldReturn(array('version'=>'29.0','version'=>'30.0'));
    }

    function it_should_return_resources(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('versionURLs');

        $this->resources()->shouldReturn('versionURLs');
    }

    function it_should_return_identity (
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->willReturn('Identity');

        $this->identity()->shouldReturn('Identity');
    }

    function it_should_return_limits(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResponse->json()->shouldBeCalled()->willReturn('limits');

        $this->limits()->shouldReturn('limits');
    }

    function it_should_return_describe(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedStorage->get('version')->shouldBeCalled()->willReturn(array('url'=>'versionURL'));
        $mockedResponse->json()->shouldBeCalled()->willReturn('describe');

        $this->describe()->shouldReturn('describe');        
    }

    function it_should_return_query(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('query');

        $this->query('query')->shouldReturn('query');
    }

    function it_should_return_queryExplain(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryExplain');

        $this->queryExplain('query')->shouldReturn('queryExplain');
    }

    function it_should_return_queryAll(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('queryAll');

        $this->queryAll('query')->shouldReturn('queryAll');
    }

    function it_should_return_quickActions(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('quickActions');

        $this->quickActions()->shouldReturn('quickActions');
    }

    function it_should_return_search(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('search');

        $this->search('query')->shouldReturn('search');
    }

    function it_should_return_ScopeOrder(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchScopeOrder');

        $this->scopeOrder()->shouldReturn('searchScopeOrder');
    }

    function it_should_return_searchLayouts(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchLayouts');

        $this->searchLayouts('objectList')->shouldReturn('searchLayouts');
    }

    function it_should_return_suggestedArticles(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get','https://na##.salesforce.com/services/data/v30.0/search/suggestTitleMatches?q=query&language=en_US&publishStatus=Online&foo=bar&flim=flam',Argument::type('Array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('suggestedArticles');

        $this->suggestedArticles('query', ['parameters'=>['foo'=>'bar','flim'=>'flam']])->shouldReturn('suggestedArticles');
    }

    function it_should_return_suggestedQueries(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get','https://na##.salesforce.com/services/data/v30.0/search/suggestSearchQueries?q=query&language=en_US&foo=bar&flim=flam',Argument::type('array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('searchSuggestedQueries');

        $this->suggestedQueries('query', ['parameters'=>['foo'=>'bar','flim'=>'flam']])->shouldReturn('searchSuggestedQueries');
    }

    function it_should_return_custom_request(
        StorageInterface $mockedStorage,
        ResponseInterface $mockedResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->createRequest('get','https://na##.salesforce.com/services/apexrest/FieldCase?foo=bar',Argument::type('array'))->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn('customRequest');
        $this->custom('/FieldCase',['parameters'=>['foo'=>'bar']])->shouldReturn('customRequest');
    }

    //Resource class

    function it_returns_a_resource(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest(Argument::type('string'),Argument::type('string'),Argument::type('array'))->willReturn($mockedRequest);
        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');
        $mockedResponse->xml()->shouldBeCalled()->willReturn('xmlResource');

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'abc',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $this->request('uri',[])->shouldReturn('jsonResource');
        $this->request('uri',['format'=>'xml'])->shouldReturn('xmlResource');
    }

    function it_should_format_header_in_json(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){
        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>['Authorization' => 'bearer accesstoken', 'Accept' => 'application/json', 'Content-Type' => 'application/json']])
            ->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri',[])->shouldReturn('jsonResource');
    }

    function it_should_format_header_in_xml(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>['Authorization' => 'bearer accesstoken', 'Accept' => 'application/xml', 'Content-Type' => 'application/xml']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->xml()->shouldBeCalled()->willReturn('xmlResource');

        $this->request('uri',['format'=>'xml'])->shouldReturn('xmlResource');
    }

    function it_should_format_header_in_urlencoding(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>['Authorization' => 'bearer accesstoken', 'Accept' => 'application/x-www-form-urlencoded', 'Content-Type' => 'application/x-www-form-urlencoded']])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $this->request('uri',['format'=>'urlencoded'])->shouldReturn($mockedResponse);
    }

    function it_should_format_header_with_gzip(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>["Authorization" => "bearer accesstoken", "Accept" => "application/json", "Content-Type" => "application/json", "Accept-Encoding" => "gzip", "Content-Encoding" => "gzip"]])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri',['compression'=>true, 'compressionType'=>'gzip'])->shouldReturn('jsonResource');
    }

    function it_should_format_header_with_deflate(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>["Authorization" => "bearer accesstoken", "Accept" => "application/json", "Content-Type" => "application/json", "Accept-Encoding" => "deflate", "Content-Encoding" => "deflate"]])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri',['compression'=>true, 'compressionType'=>'deflate'])->shouldReturn('jsonResource');
    }

    function it_should_format_header_without_compression(
        ClientInterface $mockedClient,
        StorageInterface $mockedStorage,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse){

        $mockedStorage->getToken()->willReturn(array(
            'access_token' =>'accesstoken',
            'instance_url' =>'def',
            'token_type'   =>'bearer'));

        $mockedClient->createRequest(
            "get",
            "uri",
            ['headers'=>["Authorization" => "bearer accesstoken", "Accept" => "application/json", "Content-Type" => "application/json"]])
            ->shouldBeCalled()->willReturn($mockedRequest);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);

        $mockedResponse->json()->shouldBeCalled()->willReturn('jsonResource');

        $this->request('uri',['compression'=>false])->shouldReturn('jsonResource');
    }

    function it_should_allow_a_get_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('GET',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->get('uri')->shouldReturn($mockedResponse);
    }

    function it_should_allow_a_post_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('POST',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->post('uri', array('test'=>'param'))->shouldReturn($mockedResponse);
    }

    function it_should_allow_a_put_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('PUT',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->put('uri', array('test'=>'param'))->shouldReturn($mockedResponse);
    }

    function it_should_allow_a_patch_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('PATCH',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->patch('uri', array('test'=>'param'))->shouldReturn($mockedResponse);
    }

    function it_should_allow_a_head_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('HEAD',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->head('uri')->shouldReturn($mockedResponse);
    }

    function it_should_allow_a_delete_request(
        StorageInterface $mockedStorage,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest,
        ResponseInterface $mockedResponse)
    {
        $mockedClient->createRequest('DELETE',Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedResponse->json()->shouldBeCalled()->willReturn($mockedResponse);

        $this->delete('delete')->shouldReturn($mockedResponse);
    }

}
