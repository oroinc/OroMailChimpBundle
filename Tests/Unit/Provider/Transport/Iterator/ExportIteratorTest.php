<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\ExportIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class ExportIteratorTest extends TestCase
{
    private const STREAM_FILE = __DIR__ . '/fixtures/export_list_correct.csv';

    /** @var MailChimpClient|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(MailChimpClient::class);
    }

    private function createIterator(string $methodName, array $parameters): ExportIterator
    {
        return new ExportIterator($this->client, $methodName, $parameters);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWorks(string $methodName, array $parameters, StreamInterface $body, array $expected)
    {
        $iterator = $this->createIterator($methodName, $parameters);

        $response = $this->createMock(Response::class);

        $this->client->expects($this->once())
            ->method('export')
            ->with($methodName, $parameters)
            ->willReturn($response);

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testRewindWorks(string $methodName, array $parameters, StreamInterface $body, array $expected)
    {
        $iterator = $this->createIterator($methodName, $parameters);

        $response = $this->createMock(Response::class);

        $this->client->expects($this->exactly(2))
            ->method('export')
            ->with($methodName, $parameters)
            ->willReturn($response);

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);

        // Iterate once again with rewind
        $response = $this->createMock(Response::class);

        $this->client->expects($this->once())
            ->method('export')
            ->with($methodName, $parameters)
            ->willReturn($response);

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(200);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    public function iteratorDataProvider(): array
    {
        return [
            'with content' => [
                'methodName' => 'list',
                'parameters' => ['id' => 123456789],
                'stream' => new Stream(fopen(self::STREAM_FILE, 'r')),
                'expected' => [
                    [
                        'Email Address' => 'john.doe@example.com',
                        'First Name' => 'John',
                        'Last Name' => 'Doe',
                        'Company' => 'John\'s Company',
                        'custom_description' => 'John\'s description',
                        'MEMBER_RATING' => 1,
                        'OPTIN_TIME' => '',
                        'OPTIN_IP' => null,
                        'CONFIRM_TIME' => '2014-10-07 13:32:14',
                        'CONFIRM_IP' => '62.80.189.14',
                        'LATITUDE' => null,
                        'LONGITUDE' => null,
                        'GMTOFF' => null,
                        'DSTOFF' => null,
                        'TIMEZONE' => null,
                        'CC' => null,
                        'REGION' => null,
                        'LAST_CHANGED' => '2014-10-07 13:35:31',
                        'LEID' => '191707149',
                        'EUID' => '95dde58709',
                        'NOTES' => null,
                    ],
                    [
                        'Email Address' => 'jane.doe@example.com',
                        'First Name' => 'Jane',
                        'Last Name' => 'Doe',
                        'Company' => 'Jane\'s Company',
                        'custom_description' => 'Jane\'s description',
                        'MEMBER_RATING' => 2,
                        'OPTIN_TIME' => '',
                        'OPTIN_IP' => null,
                        'CONFIRM_TIME' => '2014-10-07 13:32:22',
                        'CONFIRM_IP' => '62.80.189.14',
                        'LATITUDE' => null,
                        'LONGITUDE' => null,
                        'GMTOFF' => null,
                        'DSTOFF' => null,
                        'TIMEZONE' => null,
                        'CC' => null,
                        'REGION' => null,
                        'LAST_CHANGED' => '2014-10-07 13:32:22',
                        'LEID' => '191707153',
                        'EUID' => '6e75c7bf6c',
                        'NOTES' => null,
                    ],
                ],
            ],
            'empty' => [
                'methodName' => 'list',
                'parameters' => ['id' => 123456789],
                'stream' => $this->createMock(StreamInterface::class),
                'expected' => [],
            ]
        ];
    }
}
