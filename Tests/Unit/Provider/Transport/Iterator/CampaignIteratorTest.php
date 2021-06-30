<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\CampaignIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

class CampaignIteratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_BATCH_SIZE = 2;

    /** @var MailChimpClient|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(MailChimpClient::class);
    }

    private function createCampaignIterator(array $filters): CampaignIterator
    {
        return new CampaignIterator($this->client, $filters, self::TEST_BATCH_SIZE);
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWorks(array $filters, array $campaignValueMap, array $expected)
    {
        $iterator = $this->createCampaignIterator($filters);

        $this->client->expects($this->exactly(count($campaignValueMap)))
            ->method('getCampaigns')
            ->willReturnMap($campaignValueMap);
        $this->client->expects($this->any())
            ->method('getCampaignReport')
            ->willReturn([]);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }

        $this->assertEquals($expected, $actual);
    }

    public function iteratorDataProvider(): array
    {
        return [
            'two pages without filters' => [
                'filters' => [],
                'listValueMap' => [
                    [
                        ['offset' => 0, 'count' => 2],
                        [
                            'total_items' => 3,
                            'campaigns' => [
                                ['id' => '3d21b11eb1', 'name' => 'Campaign 1', 'report' => []],
                                ['id' => '3d21b11eb2', 'name' => 'Campaign 2', 'report' => []],
                            ]
                        ]
                    ],
                    [
                        ['offset' => 1, 'count' => 2],
                        [
                            'total_items' => 3,
                            'campaigns' => [
                                ['id' => '3d21b11eb3', 'name' => 'Campaign 3', 'report' => []],
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    ['id' => '3d21b11eb1', 'name' => 'Campaign 1', 'report' => []],
                    ['id' => '3d21b11eb2', 'name' => 'Campaign 2', 'report' => []],
                    ['id' => '3d21b11eb3', 'name' => 'Campaign 3', 'report' => []]
                ]
            ],
            'two pages with filters' => [
                'filters' => ['status' => 'sent'],
                'listValueMap' => [
                    [
                        ['offset' => 0, 'count' => 2, 'status' => 'sent'],
                        [
                            'total_items' => 3,
                            'campaigns' => [
                                ['id' => '3d21b11eb1', 'name' => 'Campaign 1', 'report' => []],
                                ['id' => '3d21b11eb2', 'name' => 'Campaign 2', 'report' => []],
                            ]
                        ]
                    ],
                    [
                        ['offset' => 1, 'count' => 2, 'status' => 'sent'],
                        [
                            'total_items' => 3,
                            'campaigns' => [
                                ['id' => '3d21b11eb3', 'name' => 'Campaign 3', 'report' => []],
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    ['id' => '3d21b11eb1', 'name' => 'Campaign 1', 'report' => []],
                    ['id' => '3d21b11eb2', 'name' => 'Campaign 2', 'report' => []],
                    ['id' => '3d21b11eb3', 'name' => 'Campaign 3', 'report' => []]
                ]
            ],
            'empty' => [
                'filters' => [],
                'listValueMap' => [
                    [
                        ['offset' => 0, 'count' => 2],
                        [
                            'total_items' => 0,
                            'campaigns' => []
                        ]
                    ]
                ],
                'expected' => []
            ],
        ];
    }
}
