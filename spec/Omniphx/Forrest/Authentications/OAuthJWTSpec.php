<?php

namespace spec\Omniphx\Forrest\Authentications;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
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

class OAuthJWTSpec extends ObjectBehavior
{
    protected $token = [
        'access_token' => '00Do0000000secret',
        'instance_url' => 'https://na17.salesforce.com',
        'id'           => 'https://login.salesforce.com/id/00Do0000000xxxxx/005o0000000xxxxx',
        'token_type'   => 'Bearer',
        'issued_at'    => '1447000236011',
        'signature'    => 'secretsig'];

    protected $settings = [
        'authenticationFlow' => 'OAuthJWT',
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
        $mockedFormatter->getDefaultMIMEType()->willReturn('application/json');

        $mockedVersionRepo->get()->willReturn(['url' => '/resources']);

        $mockedFormatter->formatResponse($mockedResponse)->willReturn(['foo' => 'bar']);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Omniphx\Forrest\Authentications\OAuthJWT');
    }
}
