<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\Context;
use Oro\Bundle\MailChimpBundle\ImportExport\DataConverter\MemberActivityDataConverter;
use PHPUnit\Framework\TestCase;

class MemberActivityDataConverterTest extends TestCase
{
    private MemberActivityDataConverter $memberActivityDataConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->memberActivityDataConverter = new MemberActivityDataConverter();
    }

    /**
     * @dataProvider dataProvider
     */
    public function testConvertToImportFormat(array $record, array $expected): void
    {
        $context = new Context(['channel' => 'channel_id']);
        $this->memberActivityDataConverter->setImportExportContext($context);
        $result = $this->memberActivityDataConverter->convertToImportFormat($record);

        self::assertEquals($expected, array_intersect_assoc($expected, $result));
    }

    public function dataProvider(): array
    {
        return [
            'With activity data' => [
                'record' => [
                    'email_address' => 'email_address@exmaple.com',
                    'activity' => [
                        'action' => 'string',
                        'timestamp' => '2000-01-01 23:59:59',
                        'ip' => '127.0.0.1'
                    ],
                ],
                'expected' => [
                    'email' => 'email_address@exmaple.com',
                    'action' => 'string',
                    'activityTime' => '2000-01-01 23:59:59',
                    'ip' => '127.0.0.1'
                ]
            ],
            'Without activity data' => [
                'record' => [
                    'email_address' => 'email_address@exmaple.com',
                ],
                'expected' => [
                    'email' => 'email_address@exmaple.com',
                ]
            ]
        ];
    }
}
