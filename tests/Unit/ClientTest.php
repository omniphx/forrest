<?php

namespace Tests\Unit;

use GuzzleHttp\ClientInterface;
use Omniphx\Forrest\Formatters\JSONFormatter;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\ResourceRepositoryInterface;
use Tests\Fixtures\InspectableClient;
use Tests\TestCase;

class ClientTest extends TestCase
{
    public function testRequestFormatsJsonBodyAndFiresResponseEvent(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $event = $this->createMock(EventInterface::class);
        $tokenRepo = $this->tokenRepo();

        $http->expects($this->once())
            ->method('request')
            ->with(
                'post',
                'https://instance.salesforce.com/services/data/v59.0/sobjects/Account',
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer token',
                        'Sforce-Auto-Assign' => 'false',
                    ],
                    'body' => '{"Name":"Dunder Mifflin"}',
                ]
            )
            ->willReturn($this->jsonResponse(['id' => '001']));

        $event->expects($this->once())->method('fire')->with('forrest.response', [['id' => '001']]);

        $client = $this->makeClient($http, $event, $tokenRepo);

        $response = $client->sobjects('Account', [
            'method' => 'post',
            'body' => ['Name' => 'Dunder Mifflin'],
            'headers' => ['Sforce-Auto-Assign' => 'false'],
        ]);

        $this->assertSame(['id' => '001'], $response);
    }

    public function testRequestRetriesAfterTokenExpiry(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($this->requestException(401)),
                $this->jsonResponse(['id' => '001'])
            );

        $client = $this->makeClient($http);

        $this->assertSame(['id' => '001'], $client->query('SELECT Id FROM Account'));
        $this->assertSame(1, $client->refreshCalls);
    }

    public function testRequestCanReturnRawResponses(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $response = $this->textResponse('raw');
        $http->expects($this->once())->method('request')->willReturn($response);

        $client = $this->makeClient($http);

        $this->assertSame($response, $client->request('https://instance.salesforce.com/services/data', ['method' => 'get', 'format' => 'raw']));
    }

    public function testQueryBuildsEncodedUrls(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'get',
                'https://instance.salesforce.com/services/data/v59.0/query?q=SELECT+Id+FROM+Account',
                $this->anything()
            )
            ->willReturn($this->jsonResponse(['totalSize' => 1]));

        $client = $this->makeClient($http);

        $this->assertSame(['totalSize' => 1], $client->query('SELECT Id FROM Account'));
    }

    public function testDynamicResourceCallsAppendStringPaths(): void
    {
        $http = $this->createMock(ClientInterface::class);
        $http->expects($this->once())
            ->method('request')
            ->with(
                'get',
                'https://instance.salesforce.com/services/data/v59.0/theme/mobile',
                $this->anything()
            )
            ->willReturn($this->jsonResponse(['theme' => 'mobile']));

        $client = $this->makeClient($http);

        $this->assertSame(['theme' => 'mobile'], $client->theme('mobile'));
    }

    private function makeClient(?ClientInterface $http = null, ?EventInterface $event = null, ?RepositoryInterface $tokenRepo = null): InspectableClient
    {
        $http = $http ?: $this->createMock(ClientInterface::class);
        $event = $event ?: $this->createStub(EventInterface::class);
        $tokenRepo = $tokenRepo ?: $this->tokenRepo();

        $instanceUrlRepo = $this->createStub(RepositoryInterface::class);
        $instanceUrlRepo->method('get')->willReturn('https://instance.salesforce.com');

        $versionRepo = $this->createStub(RepositoryInterface::class);
        $versionRepo->method('get')->willReturn(['url' => '/services/data/v59.0']);

        $resourceRepo = $this->createStub(ResourceRepositoryInterface::class);
        $resourceRepo->method('get')->willReturnMap([
            ['query', '/services/data/v59.0/query'],
            ['theme', '/services/data/v59.0/theme'],
            ['sobjects', '/services/data/v59.0/sobjects'],
        ]);

        return new InspectableClient(
            $http,
            $this->createStub(EncryptorInterface::class),
            $event,
            $this->createStub(InputInterface::class),
            $this->createStub(RedirectInterface::class),
            $instanceUrlRepo,
            $this->createStub(RepositoryInterface::class),
            $resourceRepo,
            $this->createStub(RepositoryInterface::class),
            $tokenRepo,
            $versionRepo,
            new JSONFormatter($tokenRepo, $this->settings()),
            $this->settings()
        );
    }

    private function tokenRepo(): RepositoryInterface
    {
        $tokenRepo = $this->createStub(RepositoryInterface::class);
        $tokenRepo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
            'id' => 'https://login.salesforce.com/id/org/user',
        ]);

        return $tokenRepo;
    }
}
