<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport;

use GuzzleHttp\Psr7\Response;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Exception\BadResponseException;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MailChimpClientTest extends TestCase
{
    const API_KEY = '3024ddceb22913e9f8ff39fe9be157f6-us9';
    const DC = 'us9';

    /** @var MockObject|MailChimpClient */
    protected $client;

    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(MailChimpClient::class)
            ->onlyMethods(['callExportApi'])
            ->setConstructorArgs([self::API_KEY])
            ->getMock();
    }

    public function testConstructorSavesApiKey()
    {
        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();
        $response->expects(static::any())->method('getStatusCode')->willReturn(200);
        $response->expects(static::once())->method('getHeaderLine')
            ->with('Content-Type')->willReturn('text/html');

        $this->client->expects(static::once())
            ->method('callExportApi')
            ->with('https://us9.api.mailchimp.com/export/1.0/someMethod/', \sprintf('{"apikey":"%s"}', self::API_KEY))
            ->willReturn($response);

        $this->client->export('someMethod', []);
    }

    public function testExportWorks()
    {
        $methodName = 'list';
        $parameters = ['id' => 123456];

        $expectedUrl = sprintf(
            'https://%s.api.mailchimp.com/export/%s/%s/',
            self::DC,
            MailChimpClient::EXPORT_API_VERSION,
            $methodName
        );
        $expectedRequestEntity = json_encode(array_merge(['apikey' => self::API_KEY], $parameters));

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $this->client->expects(static::once())
            ->method('callExportApi')
            ->with($expectedUrl, $expectedRequestEntity)
            ->willReturn($response);

        $response->expects(static::any())->method('getStatusCode')->willReturn(200);
        $response->expects(static::once())->method('getHeaderLine')
            ->with('Content-Type')->willReturn('text/html');

        static::assertEquals($response, $this->client->export($methodName, $parameters));
    }

    /**
     * @dataProvider invalidResponseDataProvider
     * @param int    $statusCode
     * @param string $expectedError
     */
    public function testExportFailsWithInvalidResponse(int $statusCode, $expectedError)
    {
        $methodName = 'list';
        $parameters = ['id' => 123456];
        $expectedUrl = sprintf(
            'https://%s.api.mailchimp.com/export/%s/%s/',
            self::DC,
            MailChimpClient::EXPORT_API_VERSION,
            $methodName
        );
        $expectedRequestEntity = json_encode(array_merge(['apikey' => self::API_KEY], $parameters));

        $response = $this->getMockBuilder(Response::class)->disableOriginalConstructor()->getMock();

        $this->client->expects(static::once())
            ->method('callExportApi')
            ->with($expectedUrl, $expectedRequestEntity)
            ->willReturn($response);

        $response->expects(static::any())->method('getHeaderLine')->willReturnMap(
            [
                ['Content-Type', 'application/json'],
                ['X-MailChimp-API-Error-Code', 104],
            ]
        );
        $response->expects(static::exactly(2))->method('getStatusCode')->willReturn($statusCode);
        $response->expects(static::once())->method('getReasonPhrase')->willReturn('OK');

        $response->expects(static::any())
            ->method('getBody')
            ->willReturn('{"error":"API Key can not be blank","code":104}');

        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage(
            implode(
                PHP_EOL,
                [
                    $expectedError,
                    '[status code] '.$statusCode,
                    '[API error code] 104',
                    '[reason phrase] OK',
                    '[url] https://us9.api.mailchimp.com/export/1.0/list/',
                    '[request parameters]'.$expectedRequestEntity,
                    '[content type] application/json',
                    '[response body] {"error":"API Key can not be blank","code":104}',
                ]
            )
        );

        $this->client->export($methodName, $parameters);
    }

    /**
     * @return array[]
     */
    public function invalidResponseDataProvider(): array
    {
        return [
            [200, 'Invalid response, expected content type is text/html'],
            [500, 'Request to MailChimp Export API wasn\'t successfully completed.'],
        ];
    }
}
