<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\MmbrExtdMergeVarIterator;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListProvider;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface;
use Oro\Component\Testing\ReflectionUtil;

class MmbrExtdMergeVarIteratorTest extends \PHPUnit\Framework\TestCase
{
    private function createIterator(
        \Iterator $mainIterator
    ): MmbrExtdMergeVarIterator|\PHPUnit\Framework\MockObject\MockObject {
        $iterator = $this->getMockBuilder(MmbrExtdMergeVarIterator::class)
            ->onlyMethods(['createBufferedIterator'])
            ->setConstructorArgs([
                $this->createMock(MarketingListProvider::class),
                $this->createMock(OwnershipMetadataProviderInterface::class),
                'removedItemClassName',
                'unsubscribedItemClassName'
            ])
            ->allowMockingUnknownTypes()
            ->getMock();
        $iterator->setMainIterator($mainIterator);

        return $iterator;
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testIterator(\Iterator $mainIterator, array $values, array $expected)
    {
        $iterator = $this->createIterator($mainIterator);

        $iterator->expects($this->any())
            ->method('createBufferedIterator')
            ->willReturn(new \ArrayIterator($values));

        $actual = [];
        foreach ($iterator as $value) {
            $actual[] = $value;
        }

        $this->assertEquals($expected, $actual);
    }

    public function optionsDataProvider(): array
    {
        $subscribersListId = 1;
        $staticSegmentId = 1;

        $subscribersList = new SubscribersList();
        ReflectionUtil::setId($subscribersList, $subscribersListId);

        $staticSegment = new StaticSegment();
        ReflectionUtil::setId($staticSegment, $staticSegmentId);
        $staticSegment->setSubscribersList($subscribersList);

        $mainIterator = new \ArrayIterator([$staticSegment]);

        return [
            'without array' => [
                'mainIterator' => $mainIterator,
                'values' => [101, 102],
                'expected' => [101, 102]
            ],
            'with array' => [
                'mainIterator' => $mainIterator,
                'values' => [
                    [
                        'id' => 1,
                        'member_id' => 101
                    ],
                    [
                        'id' => 2,
                        'member_id' => 102
                    ]
                ],
                'expected' => [
                    [
                        'member_id' => 101,
                        'subscribersList_id' => $subscribersListId,
                        'static_segment_id' => $staticSegmentId
                    ],
                    [
                        'member_id' => 102,
                        'subscribersList_id' => $subscribersListId,
                        'static_segment_id' => $staticSegmentId
                    ]
                ]
            ]
        ];
    }
}
