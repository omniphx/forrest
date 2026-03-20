<?php

namespace Tests\Unit\Formatters;

use Omniphx\Forrest\Formatters\BaseFormatter;
use Omniphx\Forrest\Formatters\CsvFormatter;
use Omniphx\Forrest\Formatters\CsvHeadersFormatter;
use Omniphx\Forrest\Formatters\JSONFormatter;
use Omniphx\Forrest\Formatters\URLEncodedFormatter;
use Omniphx\Forrest\Formatters\XMLFormatter;
use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Tests\TestCase;

class FormatterTest extends TestCase
{
    public function testJsonFormatterEncodesBodiesAndDecodesResponses(): void
    {
        $formatter = new JSONFormatter($this->tokenRepo(), $this->settings());

        $this->assertSame('{"foo":"bar"}', $formatter->setBody(['foo' => 'bar']));
        $this->assertSame(['foo' => 'bar'], $formatter->formatResponse($this->jsonResponse(['foo' => 'bar'])));
    }

    public function testXmlFormatterReturnsSimpleXmlObjects(): void
    {
        $formatter = new XMLFormatter($this->tokenRepo(), $this->settings());
        $xml = $formatter->formatResponse($this->textResponse('<root><value>bar</value></root>', 200, ['Content-Type' => 'application/xml']));

        $this->assertSame('bar', (string) $xml->value);
    }

    public function testBaseFormatterReturnsTextBodyAndCompressionHeaders(): void
    {
        $formatter = new BaseFormatter($this->tokenRepo(), $this->settings([
            'defaults' => [
                'compression' => true,
                'compressionType' => 'gzip',
            ],
        ]));

        $headers = $formatter->setHeaders();

        $this->assertSame('Bearer token', $headers['Authorization']);
        $this->assertSame('gzip', $headers['Accept-Encoding']);
        $this->assertSame('payload', $formatter->formatResponse($this->textResponse('payload')));
    }

    public function testCsvFormatterUsesCsvAcceptHeader(): void
    {
        $formatter = new CsvFormatter($this->tokenRepo(), $this->settings());
        $headers = $formatter->setHeaders();

        $this->assertSame('text/csv', $headers['Accept']);
        $this->assertSame('application/json', $headers['Content-Type']);
        $this->assertSame('id,name', $formatter->formatResponse($this->textResponse('id,name')));
    }

    public function testCsvHeadersFormatterReturnsHeadersAndBody(): void
    {
        $formatter = new CsvHeadersFormatter($this->tokenRepo(), $this->settings());
        $response = $this->textResponse('id,name', 200, ['X-Test' => ['yes']]);

        $formatted = $formatter->formatResponse($response);

        $this->assertSame(['yes'], $formatted['header']['X-Test']);
        $this->assertSame('id,name', $formatted['body']);
    }

    public function testUrlEncodedFormatterUsesUrlEncodedMimeType(): void
    {
        $formatter = new URLEncodedFormatter();

        $this->assertSame(
            [
                'Accept' => 'application/x-www-form-urlencoded',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            $formatter->setHeaders()
        );
        $this->assertSame('raw-body', $formatter->formatResponse($this->textResponse('raw-body')));
    }

    private function tokenRepo(): RepositoryInterface
    {
        $repo = $this->createStub(RepositoryInterface::class);
        $repo->method('get')->willReturn([
            'access_token' => 'token',
            'token_type' => 'Bearer',
        ]);

        return $repo;
    }
}
