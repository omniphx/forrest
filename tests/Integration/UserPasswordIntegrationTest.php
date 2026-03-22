<?php

namespace Tests\Integration;

use GuzzleHttp\Client as GuzzleClient;
use Omniphx\Forrest\Authentications\UserPassword;
use Omniphx\Forrest\Formatters\JSONFormatter;
use Omniphx\Forrest\Interfaces\EncryptorInterface;
use Omniphx\Forrest\Interfaces\EventInterface;
use Omniphx\Forrest\Interfaces\InputInterface;
use Omniphx\Forrest\Interfaces\RedirectInterface;
use Omniphx\Forrest\Providers\ObjectStorage;
use Omniphx\Forrest\Repositories\InstanceURLRepository;
use Omniphx\Forrest\Repositories\RefreshTokenRepository;
use Omniphx\Forrest\Repositories\ResourceRepository;
use Omniphx\Forrest\Repositories\StateRepository;
use Omniphx\Forrest\Repositories\TokenRepository;
use Omniphx\Forrest\Repositories\VersionRepository;
use Tests\TestCase;

class UserPasswordIntegrationTest extends TestCase
{
    private UserPassword $client;
    private static ?array $dotenv = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessIntegrationTestingIsEnabled();
        $this->client = $this->makeLiveClient();
        $this->client->authenticate();
    }

    public function test_it_returns_identity_for_the_authenticated_user(): void
    {
        $identity = $this->client->identity();

        $this->assertIsArray($identity);
        $this->assertSame($this->env('SF_USERNAME'), $identity['username'] ?? null);
        $this->assertNotEmpty($identity['user_id'] ?? null);
        $this->assertNotEmpty($identity['organization_id'] ?? null);
    }

    public function test_it_discovers_rest_resources_for_the_org(): void
    {
        $resources = $this->client->resources();

        $this->assertIsArray($resources);
        $this->assertArrayHasKey('query', $resources);
        $this->assertArrayHasKey('sobjects', $resources);
    }

    public function test_it_can_query_the_authenticated_user_record(): void
    {
        $identity = $this->client->identity();
        $userId = $identity['user_id'] ?? null;

        $this->assertNotEmpty($userId);

        $results = $this->client->query(sprintf(
            "SELECT Id, Username, Name, IsActive FROM User WHERE Id = '%s'",
            str_replace("'", "\\'", $userId)
        ));

        $this->assertIsArray($results);
        $this->assertSame(1, $results['totalSize'] ?? null);
        $this->assertCount(1, $results['records'] ?? []);
        $this->assertSame($userId, $results['records'][0]['Id'] ?? null);
        $this->assertSame($this->env('SF_USERNAME'), $results['records'][0]['Username'] ?? null);
        $this->assertArrayHasKey('Name', $results['records'][0] ?? []);
        $this->assertIsBool($results['records'][0]['IsActive'] ?? null);
    }

    private function skipUnlessIntegrationTestingIsEnabled(): void
    {
        if (! filter_var($this->env('RUN_SALESFORCE_INTEGRATION_TESTS', 'false'), FILTER_VALIDATE_BOOL)) {
            $this->markTestSkipped('Salesforce integration tests are disabled.');
        }

        foreach (['SF_CONSUMER_KEY', 'SF_CONSUMER_SECRET', 'SF_USERNAME', 'SF_PASSWORD'] as $key) {
            if ($this->env($key) === null || $this->env($key) === '') {
                $this->markTestSkipped("Salesforce integration test requires {$key}.");
            }
        }
    }

    private function makeLiveClient(): UserPassword
    {
        $settings = $this->settings([
            'authentication' => 'UserPassword',
            'credentials' => [
                'consumerKey' => $this->env('SF_CONSUMER_KEY'),
                'consumerSecret' => $this->env('SF_CONSUMER_SECRET'),
                'loginURL' => $this->env('SF_LOGIN_URL', 'https://login.salesforce.com'),
                'username' => $this->env('SF_USERNAME'),
                'password' => $this->env('SF_PASSWORD'),
            ],
            'storage' => [
                'type' => 'object',
            ],
        ]);

        $storage = new ObjectStorage();
        $encryptor = new class implements EncryptorInterface {
            public function encrypt($token)
            {
                return $token;
            }

            public function decrypt($token)
            {
                return $token;
            }
        };

        $tokenRepo = new TokenRepository($encryptor, $storage);
        $refreshTokenRepo = new RefreshTokenRepository($encryptor, $storage);
        $resourceRepo = new ResourceRepository($storage);
        $stateRepo = new StateRepository($storage);
        $versionRepo = new VersionRepository($storage);
        $instanceURLRepo = new InstanceURLRepository($tokenRepo, $settings);
        $formatter = new JSONFormatter($tokenRepo, $settings);

        return new UserPassword(
            new GuzzleClient($settings['client']),
            $encryptor,
            new class implements EventInterface {
                public function fire($event, $payload = [], $halt = false)
                {
                    return null;
                }
            },
            new class implements InputInterface {
                public function get($parameter)
                {
                    return null;
                }
            },
            new class implements RedirectInterface {
                public function to($parameter)
                {
                    return $parameter;
                }
            },
            $instanceURLRepo,
            $refreshTokenRepo,
            $resourceRepo,
            $stateRepo,
            $tokenRepo,
            $versionRepo,
            $formatter,
            $settings
        );
    }

    private function env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value !== false) {
            return $value;
        }

        $dotenv = $this->dotenv();

        return $dotenv[$key] ?? $default;
    }

    private function dotenv(): array
    {
        if (self::$dotenv !== null) {
            return self::$dotenv;
        }

        $paths = [
            dirname(__DIR__, 2).'/.env',
            dirname(__DIR__, 4).'/.env',
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                self::$dotenv = $this->parseDotenvFile($path);

                return self::$dotenv;
            }
        }

        self::$dotenv = [];

        return self::$dotenv;
    }

    private function parseDotenvFile(string $path): array
    {
        $values = [];

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            if ($name === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $values[$name] = $value;
        }

        return $values;
    }
}
