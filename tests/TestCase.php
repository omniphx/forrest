<?php

namespace Tests;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function settings(array $overrides = []): array
    {
        $defaults = [
            'authentication' => 'WebServer',
            'credentials' => [
                'consumerKey' => 'testingClientId',
                'consumerSecret' => 'testingClientSecret',
                'callbackURI' => 'https://app.test/callback',
                'loginURL' => 'https://login.salesforce.com',
                'username' => 'user@example.com',
                'password' => 'secret',
                'privateKey' => $this->privateKey(),
            ],
            'parameters' => [
                'display' => '',
                'immediate' => false,
                'state' => '',
                'scope' => '',
                'prompt' => '',
            ],
            'defaults' => [
                'method' => 'get',
                'format' => 'json',
                'compression' => false,
                'compressionType' => 'gzip',
            ],
            'client' => [
                'http_errors' => true,
                'verify' => false,
            ],
            'storage' => [
                'type' => 'session',
                'path' => 'forrest_',
                'expire_in' => 60,
                'store_forever' => false,
            ],
            'version' => '',
            'instanceURL' => '',
            'language' => 'en_US',
        ];

        return array_replace_recursive($defaults, $overrides);
    }

    protected function jsonResponse(array $payload, int $status = 200, array $headers = []): Response
    {
        $headers += ['Content-Type' => 'application/json'];

        return new Response($status, $headers, json_encode($payload));
    }

    protected function textResponse(string $payload, int $status = 200, array $headers = []): Response
    {
        return new Response($status, $headers, $payload);
    }

    protected function requestException(int $status, string $body = '{}'): RequestException
    {
        return new RequestException(
            'Request failed',
            new Request('GET', 'https://example.test'),
            new Response($status, ['Content-Type' => 'application/json'], $body)
        );
    }

    private function privateKey(): string
    {
        return <<<'PEM'
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEAxDTVvT3ZEwecV2LSvdim2uj8OW/qbzE4hnM6D4jRmM7loWse
kNSEJNOndax86YZ6x4tQX7B0URx3uIfoYqfG/9W8Xv8eXjM8mXQbTBxkXYn4NQF7
Xga9jRDFE1j7xc7Tn7D9Q7c5yJn9OdqDWv0ro9V8uP58rC9xtY7aQJmHt+6eTQe7
9NiVxkY8t+43pEk6kF2wrvtLBtSOB2Lfdsg0YVubGJ+bxq2BljB6k7gtcq1vS7O/
x9v4HZl8F5FG+s2p10WQANz5S1qq8piDnlYy7vZBeZ9QmC8v4dfmM5LHFihudDgh
2C7tRZ2fELrT8xMa8rTVZ89SZT6aW5bGHn1w6QIDAQABAoIBAAjL9KFo0NNeFfPT
0Gn6a6aFIV6mDcMuOMH0zjJWrR7YjMXcui8DtmrZllLSPEMoHgdrYn4+6kvd6B9U
sax3QG2DkqOo0sWHdHQGlT37+N+b9oUPX2zA3TDoJfsh6v5Njr34lF+Au3PX+Hkg
qgWjFjry3J9w0MQbUe1p1Hu9rO1H+8Y+RF0RF/AgorS54Hq0mgm4nKNzYj0Jkgcu
EsjLi9T6rYYzCKPlP6wRbda44d/SGy7G2g7v8pXABnmy0H2gJS6Lf3bl+8hMPuhN
8j2NIlEqe1yehnQws7n6dNWF4Nn+q7jJHGqGC0m0v0F59M4Mew/5fJpkdyVjIlRh
mEIDm0ECgYEA80KWTmfikAMv5M9qs4lBQNVvB4cGb2PdV1lhctY6P2R1pnudB6Ci
0evH4k+oWPS8lQYCKebc/OaiaQ2e2gD2OrM0mAE7I11nGiRNNVkM6l7T6hE5D5f0
EQ+Aw2R0hB3+IZLdgDCLyEhSBJ4mCVsVhDE3XgpT2uT+pxvks2aLuTECgYEAza+L
uiz7I/VmOO15zM8gDqSQU0z7wfad+FVdrJyyf0cH5QUw9To7nfxNU4JA1iKtr9SE
IpkVfZWdgfSz/LVeDH+Dy8oSx75l2WdSL8IjRkb6xbk6nEtkSg/RAD6RY9uCgV4C
35mKJdQwEZrYjNh5lJ9c4ScbxVdSQYlwB7NEx+ECgYEAnDB1Lx7c6sNFeS0zvuqO
7pY0v3C7DgRMQ2GOa6C4/4+Bz1S8mvdm+i8Vnl4ucXU4pPpJbKx8JqGqSVuQ9DUx
BrS3C6lSlrDAhKHY6vDhQGsifHVBKtQ6Sn4h4j09l5HmjWGlntCCeWIdAvmRZaL6
nP7b0aU8As6fMd40uUOwLtECgYEAjTQzI0/gpa4mSrW/hmEyuA28shb5K5GsY5Nw
3TN+Fql5PRA5aRtwf39pWEwqlS27zGQxb2d8i1mO5IYcd4s6tyGRZ1BmPnWstUvF
jxV2M5V5r7E6D8SNvR7cyI1rHqunKqAmOO0c+qA7tJf1uQAkKEKTyZ2v0M+HhbiK
9Mgp9AECgYBv2srQwRA0mwPMA2H7uL3N3LZQG8XYp/fj8CO4w16RjWFl5wM9PQ+5
6zIgq4J2aKk8CBNd4s1Dul7hzR7tWnAxtfLqHLpQ41L1Z1HeV8V9ULy/3CpF3D8k
yvbyZ73pXnWwQku5yO4w8ihHX1t8YRlTZ7M8hD0lFcpE+XMUHDc0bg==
-----END RSA PRIVATE KEY-----
PEM;
    }
}
