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

class UserPasswordSpec extends ObjectBehavior
{
    function let(
        ClientInterface $mockedClient,
        ResponseInterface $mockedResponse,
        RequestInterface $mockedRequest,
        SessionInterface $mockedSession,
        RedirectInterface $mockedRedirect,
        InputInterface $mockedInput) {

        $settings  = array(
            'authenticationFlow' => 'UserPassword',
            'oauth' => array(
                'consumerKey'    => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI'    => 'callbackURL',
                'loginURL'       => 'https://login.salesforce.com',
                'username' => '',
                'password' => '',

            ),
            'optional' => array(
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
                'debug'  => false,
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
        $mockedSession->putToken(Argument::any())->willReturn(null);
        $mockedSession->put(Argument::any(),Argument::any())->willReturn(null);

        $mockedClient->send(Argument::any())->willReturn($mockedResponse);
        $mockedClient->createRequest(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedRequest);
        $mockedClient->post(Argument::any(),Argument::any(),Argument::any())->willReturn($mockedResponse);

        $this->beConstructedWith(
            $mockedClient,
            $mockedSession,
            $mockedRedirect,
            $mockedInput,
            $settings);

    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\UserPassword');
    }

    function it_should_authenticate(
        ResponseInterface $versionResponse,
        ClientInterface $mockedClient,
        RequestInterface $mockedRequest)
    {
        $mockedClient->send(Argument::any())->shouldBeCalled(1)->willReturn($versionResponse);

        $versionResponse->json()->shouldBeCalled()->willReturn([['version'=>'30.0'],['version'=>'31.0']]);

        $this->authenticate('url')->shouldReturn(null);
    }

    function it_should_refresh(ResponseInterface $mockedResponse)
    {
        $mockedResponse->json()->willReturn('json_response');
        $this->refresh()->shouldReturn('json_response');
    }
}
