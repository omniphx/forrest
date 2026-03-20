<?php

namespace Tests\Unit\Authentications;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Authentications\ClientCredentials;
use Omniphx\Forrest\Authentications\OAuthJWT;
use Omniphx\Forrest\Authentications\UserPassword;
use Omniphx\Forrest\Authentications\UserPasswordSoap;
use Omniphx\Forrest\Authentications\WebServer;
use Omniphx\Forrest\Formatters\JSONFormatter;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\ResourceRepositoryInterface;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function testWebServerAuthenticateBuildsAuthorizeRedirectUrl(): void
    {
        $redirect = $this->createMock(RedirectInterface::class);
        $redirect->expects($this->once())
            ->method('to')
            ->with($this->stringContains('/services/oauth2/authorize?response_type=code'))
            ->willReturn('redirected');

        $client = $this->makeWebServer(
            $this->createStub(ClientInterface::class),
            $redirect,
            $this->createStub(InputInterface::class),
            $this->tokenRepoWithoutExpectations(),
            $this->resourceRepoWithoutExpectations(),
            $this->versionRepoWithoutExpectations()
        );

        $this->assertSame('redirected', $client->authenticate());
    }

    public function testWebServerCallbackStoresStateTokensVersionAndResources(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $input = $this->createStub(InputInterface::class);
        $tokenRepo = $this->createMock(RepositoryInterface::class);
        $refreshRepo = $this->createMock(RepositoryInterface::class);
        $stateRepo = $this->createMock(RepositoryInterface::class);
        $versionRepo = $this->createMock(RepositoryInterface::class);
        $resourceRepo = $this->createMock(ResourceRepositoryInterface::class);
        $instanceUrlRepo = $this->createStub(RepositoryInterface::class);
        $instanceUrlRepo->method('get')->willReturn('https://instance.salesforce.com');
        $versionRepo->method('get')->willReturn(['url' => '/services/data/v59.0']);

        $input->method('get')->willReturnMap([
            ['code', 'callback-code'],
            ['state', urlencode(json_encode(['loginUrl' => 'https://login.salesforce.com']))],
        ]);

        $tokenRepo->expects($this->once())->method('put')->with($this->arrayHasKey('access_token'));
        $tokenRepo->method('get')->willReturn([
            'access_token' => '00Do0000000secret',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);
        $refreshRepo->expects($this->once())->method('put')->with('refresh-token');
        $stateRepo->expects($this->once())->method('put')->with(['loginUrl' => 'https://login.salesforce.com']);
        $versionRepo->expects($this->atLeastOnce())->method('put');
        $resourceRepo->expects($this->once())->method('put')->with(['query' => '/services/data/v59.0/query']);

        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $this->jsonResponse([
                    'access_token' => '00Do0000000secret',
                    'instance_url' => 'https://na17.salesforce.com',
                    'id' => 'https://login.salesforce.com/id/org/user',
                    'token_type' => 'Bearer',
                    'refresh_token' => 'refresh-token',
                ]),
                $this->jsonResponse([
                    ['label' => 'Winter 24', 'url' => '/services/data/v59.0', 'version' => '59.0'],
                ]),
                $this->jsonResponse([
                    'query' => '/services/data/v59.0/query',
                ])
            );

        $client = new WebServer(
            $http,
            $this->createStub(EncryptorInterface::class),
            $this->createStub(EventInterface::class),
            $input,
            $this->createStub(RedirectInterface::class),
            $instanceUrlRepo,
            $refreshRepo,
            $resourceRepo,
            $stateRepo,
            $tokenRepo,
            $versionRepo,
            new JSONFormatter($tokenRepo, $this->settings()),
            $this->settings()
        );

        $this->assertSame(['loginUrl' => 'https://login.salesforce.com'], $client->callback());
    }

    public function testUserPasswordAuthenticateStoresReturnedToken(): void
    {
        $tokenRepo = $this->createMock(RepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('put')->with($this->arrayHasKey('access_token'));
        $tokenRepo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);

        $client = $this->makePasswordClient(UserPassword::class, $tokenRepo, [
            $this->jsonResponse([
                'access_token' => 'token',
                'instance_url' => 'https://instance.salesforce.com',
                'id' => 'https://login.salesforce.com/id/org/user',
                'token_type' => 'Bearer',
            ]),
            $this->jsonResponse([
                ['label' => 'Winter 24', 'url' => '/services/data/v59.0', 'version' => '59.0'],
            ]),
            $this->jsonResponse(['query' => '/services/data/v59.0/query']),
        ]);

        $client->authenticate();
        $this->addToAssertionCount(1);
    }

    public function testClientCredentialsAuthenticateUsesClientCredentialsGrant(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $tokenRepo = $this->createMock(RepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('put')->with($this->arrayHasKey('access_token'));
        $tokenRepo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);

        $calls = 0;
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturnCallback(function ($method, $url, $options) use (&$calls) {
                $calls++;

                if ($calls === 1) {
                    $this->assertSame('post', $method);
                    $this->assertSame('https://login.salesforce.com/services/oauth2/token', $url);
                    $this->assertSame([
                        'form_params' => [
                            'grant_type' => 'client_credentials',
                            'client_id' => 'testingClientId',
                            'client_secret' => 'testingClientSecret',
                        ],
                    ], $options);

                    return $this->jsonResponse([
                        'access_token' => 'token',
                        'instance_url' => 'https://instance.salesforce.com',
                        'id' => 'https://login.salesforce.com/id/org/user',
                        'token_type' => 'Bearer',
                    ]);
                }

                if ($calls === 2) {
                    return $this->jsonResponse([
                        ['label' => 'Winter 24', 'url' => '/services/data/v59.0', 'version' => '59.0'],
                    ]);
                }

                return $this->jsonResponse(['query' => '/services/data/v59.0/query']);
            });

        $client = $this->makePasswordStyleClient(ClientCredentials::class, $http, $tokenRepo);
        $client->authenticate();
        $this->addToAssertionCount(1);
    }

    public function testOauthJwtAuthenticateStoresReturnedToken(): void
    {
        $client = $this->makePasswordClient(OAuthJWT::class, $this->tokenRepo(), [
            $this->jsonResponse([
                'access_token' => 'token',
                'instance_url' => 'https://instance.salesforce.com',
                'id' => 'https://login.salesforce.com/id/org/user',
                'token_type' => 'Bearer',
            ]),
            $this->jsonResponse([
                ['label' => 'Winter 24', 'url' => '/services/data/v59.0', 'version' => '59.0'],
            ]),
            $this->jsonResponse(['query' => '/services/data/v59.0/query']),
        ]);

        $client->authenticate();
        $this->addToAssertionCount(1);
    }

    public function testUserPasswordSoapTransformsSoapLoginResponses(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $this->textResponse(
                    '<?xml version="1.0" encoding="utf-8"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><loginResponse><result><serverUrl>https://na17.salesforce.com/services/Soap/u/46.0/00Dxx</serverUrl><sessionId>session-token</sessionId><userId>005xx</userId><userInfo><organizationId>00Dxx</organizationId></userInfo><sandbox>false</sandbox></result></loginResponse></soapenv:Body></soapenv:Envelope>'
                ),
                $this->jsonResponse([
                    ['label' => 'Winter 24', 'url' => '/services/data/v59.0', 'version' => '59.0'],
                ]),
                $this->jsonResponse(['query' => '/services/data/v59.0/query'])
            );

        $tokenRepo = $this->createMock(RepositoryInterface::class);
        $tokenRepo->expects($this->once())->method('put')->with($this->callback(function (array $token): bool {
            return $token['access_token'] === 'session-token'
                && $token['instance_url'] === 'https://na17.salesforce.com'
                && $token['token_type'] === 'Bearer';
        }));
        $tokenRepo->method('get')->willReturn([
            'access_token' => 'session-token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/00Dxx/005xx',
        ]);

        $client = new UserPasswordSoap(
            $http,
            $this->createStub(EncryptorInterface::class),
            $this->createStub(EventInterface::class),
            $this->createStub(InputInterface::class),
            $this->createStub(RedirectInterface::class),
            $this->instanceUrlRepo(),
            $this->createStub(RepositoryInterface::class),
            $this->resourceRepo(),
            $this->createStub(RepositoryInterface::class),
            $tokenRepo,
            $this->versionRepo(),
            new JSONFormatter($tokenRepo, $this->settings()),
            $this->settings()
        );

        $client->authenticate();
        $this->addToAssertionCount(1);
    }

    private function makeWebServer(
        ClientInterface $http,
        RedirectInterface $redirect,
        InputInterface $input,
        ?RepositoryInterface $tokenRepo = null,
        ?ResourceRepositoryInterface $resourceRepo = null,
        ?RepositoryInterface $versionRepo = null
    ): WebServer
    {
        $tokenRepo = $tokenRepo ?: $this->tokenRepo();
        $resourceRepo = $resourceRepo ?: $this->resourceRepo();
        $versionRepo = $versionRepo ?: $this->versionRepo();

        return new WebServer(
            $http,
            $this->createStub(EncryptorInterface::class),
            $this->createStub(EventInterface::class),
            $input,
            $redirect,
            $this->instanceUrlRepo(),
            $this->createStub(RepositoryInterface::class),
            $resourceRepo,
            $this->createStub(RepositoryInterface::class),
            $tokenRepo,
            $versionRepo,
            new JSONFormatter($tokenRepo, $this->settings()),
            $this->settings()
        );
    }

    /**
     * @param class-string<UserPassword|OAuthJWT> $className
     * @param array<int, \Psr\Http\Message\ResponseInterface> $responses
     */
    private function makePasswordClient(string $className, RepositoryInterface $tokenRepo, array $responses)
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->exactly(count($responses)))->method('request')->willReturnOnConsecutiveCalls(...$responses);

        return $this->makePasswordStyleClient($className, $http, $tokenRepo);
    }

    /**
     * @param class-string<UserPassword|ClientCredentials|OAuthJWT> $className
     */
    private function makePasswordStyleClient(string $className, ClientInterface $http, RepositoryInterface $tokenRepo)
    {
        return new $className(
            $http,
            $this->createStub(EncryptorInterface::class),
            $this->createStub(EventInterface::class),
            $this->createStub(InputInterface::class),
            $this->createStub(RedirectInterface::class),
            $this->instanceUrlRepo(),
            $this->createStub(RepositoryInterface::class),
            $this->resourceRepo(),
            $this->createStub(RepositoryInterface::class),
            $tokenRepo,
            $this->versionRepo(),
            new JSONFormatter($tokenRepo, $this->settings()),
            $this->settings()
        );
    }

    private function instanceUrlRepo(): RepositoryInterface
    {
        $repo = $this->createStub(RepositoryInterface::class);
        $repo->method('get')->willReturn('https://instance.salesforce.com');

        return $repo;
    }

    private function resourceRepo(): ResourceRepositoryInterface
    {
        $repo = $this->createStub(ResourceRepositoryInterface::class);
        $repo->method('get')->willReturn('/services/data/v59.0/query');

        return $repo;
    }

    private function tokenRepo(): RepositoryInterface
    {
        $repo = $this->createMock(RepositoryInterface::class);
        $repo->expects($this->once())->method('put')->with($this->arrayHasKey('access_token'));
        $repo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);

        return $repo;
    }

    private function tokenRepoWithoutExpectations(): RepositoryInterface
    {
        $repo = $this->createStub(RepositoryInterface::class);
        $repo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);

        return $repo;
    }

    private function versionRepo(): RepositoryInterface
    {
        $repo = $this->createMock(RepositoryInterface::class);
        $repo->expects($this->atLeastOnce())->method('put');
        $repo->method('get')->willReturn(['url' => '/services/data/v59.0']);

        return $repo;
    }

    private function versionRepoWithoutExpectations(): RepositoryInterface
    {
        $repo = $this->createStub(RepositoryInterface::class);
        $repo->method('get')->willReturn(['url' => '/services/data/v59.0']);

        return $repo;
    }

    private function resourceRepoWithoutExpectations(): ResourceRepositoryInterface
    {
        $repo = $this->createStub(ResourceRepositoryInterface::class);
        $repo->method('get')->willReturn('/services/data/v59.0/query');

        return $repo;
    }
}
