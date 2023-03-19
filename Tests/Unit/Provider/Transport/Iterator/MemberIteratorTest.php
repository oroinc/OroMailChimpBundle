<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\MemberIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

class MemberIteratorTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_LIST_ID = 42;
    private const TEST_LIST_ORIGIN_ID = 100;

    /** @var MailChimpClient|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    protected function setUp(): void
    {
        $this->client = $this->createMock(MailChimpClient::class);
    }

    private function createIterator(
        \Iterator $subscriberLists,
        array $parameters
    ): MemberIterator|\PHPUnit\Framework\MockObject\MockObject {
        return $this->getMockBuilder(MemberIterator::class)
            ->onlyMethods(['createExportIterator'])
            ->setConstructorArgs([$subscriberLists, $this->client, $parameters])
            ->getMock();
    }

    /**
     * @dataProvider iteratorDataProvider
     */
    public function testIteratorWorks(array $parameters, array $expectedValueMap, array $expected)
    {
        $list = $this->createMock(SubscribersList::class);
        $list->expects($this->atLeastOnce())
            ->method('getOriginId')
            ->willReturn(self::TEST_LIST_ORIGIN_ID);
        $list->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(self::TEST_LIST_ID);

        $subscriberLists = new \ArrayIterator([$list]);

        $iterator = $this->createIterator($subscriberLists, $parameters);

        $iterator->expects($this->exactly(count($expectedValueMap)))
            ->method('createExportIterator')
            ->willReturnMap($expectedValueMap);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }

        $this->assertEquals($expected, $actual);
    }

    public function iteratorDataProvider(): array
    {
        $memberFoo = ['email' => 'foo@example.com'];
        $memberBar = ['email' => 'bar@example.com'];
        $memberBaz = ['email' => 'baz@example.com'];

        return [
            'empty status' => [
                'parameters' => ['include_empty' => true],
                'expectedValueMap' => [
                    [
                        MailChimpClient::EXPORT_LIST,
                        [
                            'include_empty' => true,
                            'status' => Member::STATUS_SUBSCRIBED,
                            'id' => self::TEST_LIST_ORIGIN_ID
                        ],
                        new \ArrayIterator([$memberFoo, $memberBar, $memberBaz])
                    ]
                ],
                'expected' => [
                    $this->passMember($memberFoo, Member::STATUS_SUBSCRIBED),
                    $this->passMember($memberBar, Member::STATUS_SUBSCRIBED),
                    $this->passMember($memberBaz, Member::STATUS_SUBSCRIBED),
                ]
            ],
            'single status' => [
                'parameters' => ['status' => Member::STATUS_UNSUBSCRIBED],
                'expectedValueMap' => [
                    [
                        MailChimpClient::EXPORT_LIST,
                        ['status' => Member::STATUS_UNSUBSCRIBED, 'id' => self::TEST_LIST_ORIGIN_ID],
                        new \ArrayIterator([$memberFoo, $memberBar, $memberBaz])
                    ]
                ],
                'expected' => [
                    $this->passMember($memberFoo, Member::STATUS_UNSUBSCRIBED),
                    $this->passMember($memberBar, Member::STATUS_UNSUBSCRIBED),
                    $this->passMember($memberBaz, Member::STATUS_UNSUBSCRIBED),
                ]
            ],
            'multiple statuses' => [
                'parameters' => ['status' => [Member::STATUS_SUBSCRIBED, Member::STATUS_UNSUBSCRIBED]],
                'expectedValueMap' => [
                    [
                        MailChimpClient::EXPORT_LIST,
                        ['status' => Member::STATUS_SUBSCRIBED, 'id' => self::TEST_LIST_ORIGIN_ID],
                        new \ArrayIterator([$memberFoo, $memberBar])
                    ],
                    [
                        MailChimpClient::EXPORT_LIST,
                        ['status' => Member::STATUS_UNSUBSCRIBED, 'id' => self::TEST_LIST_ORIGIN_ID],
                        new \ArrayIterator([$memberBaz])
                    ],
                ],
                'expected' => [
                    $this->passMember($memberFoo, Member::STATUS_SUBSCRIBED),
                    $this->passMember($memberBar, Member::STATUS_SUBSCRIBED),
                    $this->passMember($memberBaz, Member::STATUS_UNSUBSCRIBED),
                ]
            ],
        ];
    }

    private function passMember(array $member, string $status): array
    {
        $member['list_id'] = self::TEST_LIST_ORIGIN_ID;
        $member['subscribersList_id'] = self::TEST_LIST_ID;
        $member['status'] = $status;

        return $member;
    }
}
