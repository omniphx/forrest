<?php

namespace spec\Omniphx\Forrest\Authentications;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Omniphx\Forrest\Authentications\OAuthJWT;
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
    protected $currentTime = '2020-01-01 00:00:00';
    protected $jwtExpiration = '2020-01-01 00:03:00';

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
            'consumerSecret' => '-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAxxceYYRDCpErWPqwLE9DjvAmTDoIKmX1PxawLPLY9TPeFgrG
FHEuf/BjP30z3RUcHclCYsNeMT33Ou/T7QHpgPG6b5Er2X0+xjj89YUhLj5T3tWG
vUGtfpuortbLDdFKgVSZYk24P0L/pgRMOTmDSEMh+rLueio0YiGFc4aE0IEWNqOL
ZEzGGef0rew1z7Sui1lFAoPxm3WJU+0umtfwVwOnPkmUtLIGQGB2Q7n8CDyw9lk3
4Iojjv1gEWp4bCMo6tAdjWg2DuNUmsZpIwzXpC4Xi6WJ2qUjc4exfltgDZjWCSzN
u68oEDFWkL32zrALnHrLjbGyG9vln2TvGy1+GQIDAQABAoIBAChu+46Wi/8TaJhT
oX/+QRxAjaahipMBzgMYGoOmdoWmGQ6k9YGlUupM6fs09FmMNf+epkrknralfRaN
Kp9R6hhz/4c1FpC/LQaZAFbkyM5ZfjMdbpX1RsUV2/ZWTTrrLJSDl/stCaRfeQhA
izJ8CbudVsNRn7lT5PuhDzddNJAbq4I7Hr3LoEiQy+Wxv3hkNFSTHDzP2mwyqh52
JLGeeYk/F81sQ3ltvxQUdrD7V5vQ2h9VkQEQky65wAsm2STbSdu9hTcNCcyVv5f6
wAkJzru/nVkoqn5hBSybLlWk7l1x6RVxKfB6xzvbPk5JDFlnkLWj2jBXkeIct1Jc
23XibQECgYEA8Jp6nfIbCjf//QIrkVl5ad9JIcDe/FI/KQ7r1CQnNdRQzwCJg4eQ
o9ndCeK+cTTYzX3W+q2NsSBdV6A+xuKFZjza2YJ3Q3m8RrKtA33lWURxsD3PwuzS
sTtwXNdsW+h9HYJH7OhmjhqlBF4iTnWcNlgEqtg4HyG+2sG0bBE36ykCgYEA09SU
T0A32USN1GMOXMtnh75/6HrX8StDkHKLqN1WTuJkqK+JCqSMRnn8lKBWbeBEk80k
kIuzKXkb2C/MLGhpH5jGR2DfUC5Mtdw0yRZUATW5EwHcoYTG8/n/gFXIICRVaV+n
ErlrdVN55GHvbV5tEzcQYo+qieejOjLQcXHwSXECgYEAhYJS8/36Pytf4vcnUdpC
YxtBq3coxP6miZP8DJWbJGWSCauUouXAvwsPeoLVhl/6xdxERIm1jEoXQZ5r91SP
DXJLRlL89vZAIULYeo2LjINMSq2h8doT98Cx0vK+8CkL9Cns22sCLWxfkRLjGoJs
kkM5I8wjKDNDgoPmJ+lODDECgYB/w2/QfQMyYE7LExPOlEBVd2jeZ3lnVJjjvrLN
nvI3kgT0WStm5+hTebAGVM7MZr/2BX1QUXI2SX2p3upevnrpO9QbqSoHymUqKy8L
OhRgxm5iMHVKVjNJZDfex950xHVfoPm8KWnO0hJq1Ub7yEAxnrybNdu+YZ/pskxW
oEo1gQKBgA1UiBkEnFvX6eYplJVe4fsvDFjaPLKDMdfKqPJTsUfzbwrzuKqBWWU/
oKYBQx5bP3wfNtI9j5dp1kIePcDBIuaNoPzpG1+UV36Wofae2OaQGN5eA89X9hja
jrskEKQvdXS8iJl4zv2NtM5sCmHBrEzuIu0Hm5Mkp3IeDpi+TPtE
-----END RSA PRIVATE KEY-----',
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

        $mockedInstanceURLRepo->get()
            ->willReturn('https://instance.salesforce.com');

        $mockedResourceRepo->get(Argument::any())
            ->willReturn('/services/data/v30.0/resource');
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

        $mockedFormatter->formatResponse($mockedResponse)
            ->willReturn(['foo' => 'bar']);

        // Fake the current timestamp
        Carbon::setTestNow($this->currentTime);
    }

    public function letGo()
    {
        // Reset Carbon
        Carbon::setTestNow();
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OAuthJWT::class);
    }

    public function it_should_authenticate(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        ResponseInterface $mockedVersionRepo,
        FormatterInterface $mockedFormatter,
        ResponseInterface $versionResponse,
        Stream $body)
    {
        $url = 'url';
        $mockedHttpClient->request(
            'post',
            $url,
            ['form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6eyJhbGciOiJSUzI1NiJ9fQ.eyJpc3MiOiJ0ZXN0aW5nQ2xpZW50SWQiLCJhdWQiOiJ1cmwiLCJzdWIiOiJ1c2VyQGVtYWlsLmNvbSIsImV4cCI6MTU3Nzg1ODU4MH0.ZVUg0DnDPwbevGBhxNn3q7WPXeJxp53Jls3I8e3TLq4JxPJbQ0KH9YagHK0rrVxtBzfxLbXJZ_EHPBGAfrj2Th1RfURFvs_padt6a1CgKiOaEqzNBNJPquGDm2I06afJsbcTXurD7BRmWWRqbW5Qd1jCyX0Lr_YZiynBoQ91N82ZEAn_IkJ6l9Yr50sMxkgunW9iB66Ah4Xj8RmQ743BNpeUUZXUMGPKJ63jwRlU-wrMyn5MGSb7iYBESvWbwTtR-EOPGBk7HWo__dRS-1J3xF5PdP41UZSPUV_mwLYyM42suTvf9H_tfbDnh6ggQQGKpJdgJOGbpSlNZOreJK7pwA'
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

        $body->getContents()->shouldBeCalled()
            ->willReturn($this->authenticationJSON);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($body);

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

        $mockedFormatter->formatResponse($versionResponse)->shouldBeCalled()
            ->willReturn($this->versionArray);

        $mockedVersionRepo->has()->willReturn(false);
        $mockedVersionRepo->put([
            "label" => "Winter 16",
            "url" => "/services/data/v35.0",
            "version" => "35.0"
        ])->shouldBeCalled();

        $this->authenticate($url)->shouldReturn(null);
    }

    public function it_should_refresh(
        ClientInterface $mockedHttpClient,
        ResponseInterface $mockedResponse,
        ResponseInterface $mockedVersionRepo,
        FormatterInterface $mockedFormatter,
        ResponseInterface $versionResponse,
        Stream $body)
    {
        $url = 'https://login.salesforce.com/services/oauth2/token';
        $mockedHttpClient->request(
            'post',
            $url,
            ['form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImtpZCI6eyJhbGciOiJSUzI1NiJ9fQ.eyJpc3MiOiJ0ZXN0aW5nQ2xpZW50SWQiLCJhdWQiOiJodHRwczpcL1wvbG9naW4uc2FsZXNmb3JjZS5jb21cL3NlcnZpY2VzXC9vYXV0aDJcL3Rva2VuIiwic3ViIjoidXNlckBlbWFpbC5jb20iLCJleHAiOjE1Nzc4NTg1ODB9.ldMUERKDZhZX9gSB8huX0Odqqs6EpOmB6Ow5URKxa6V65fOZ3fEVPrjSxTmzyIfDAShKfxFeuLIXHSanPYJzQ3C5bhP7S_HAFDHJnQFbVKPYp9IcmdJOj2U-JnMv7oDc5ejXMxF-CNzRQYN4ZOwONH7pEmW1-8QTwdFUck7QHdglWF1C6K6BLN0boyjCdrrdFCGtB-XfmxxJSfiT8MZY7uS3rWBXBLDNUx4Nn9qKiJQr5kxVY3g2zjzevR1xJgmrXZFZpw__SuQpY5F4CuLfPwcc7x9HPJCVdKsdnJKpZ4jkzb4zMocarN19bp_L2tPmjNVBQDyW6V16o1LN1pSbOg'
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

        $body->getContents()->shouldBeCalled()
            ->willReturn($this->authenticationJSON);
        $mockedResponse->getBody()->shouldBeCalled()->willReturn($body);

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

        $mockedFormatter->formatResponse($versionResponse)->shouldBeCalled()
            ->willReturn($this->versionArray);

        $mockedVersionRepo->has()->willReturn(false);
        $mockedVersionRepo->put([
            "label" => "Winter 16",
            "url" => "/services/data/v35.0",
            "version" => "35.0"
        ])->shouldBeCalled();

        $this->refresh()->shouldReturn(null);
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
}
