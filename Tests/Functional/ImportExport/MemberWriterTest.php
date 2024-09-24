<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\ImportExport;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\ImportExport\Writer\MemberWriter;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadMemberData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MemberWriterTest extends WebTestCase
{
    /** @var MailChimpTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var StepExecution|\PHPUnit\Framework\MockObject\MockObject */
    private $stepExecution;

    private MemberWriter $writer;
    private int $oldWaitTime;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadMemberData::class]);

        $this->transport = $this->createMock(MailChimpTransport::class);
        $this->stepExecution = $this->createMock(StepExecution::class);
        $this->stepExecution->expects($this->any())
            ->method('getJobExecution')
            ->willReturn($this->createMock(JobExecution::class));

        $this->getContainer()->set('oro_mailchimp.tests.transport.integration_transport', $this->transport);

        $this->writer = $this->getContainer()->get('oro_mailchimp.importexport.writer.member');
        $this->oldWaitTime = $this->writer->getWaitTime();
        $this->writer->setWaitTime(0);
        $this->writer->setStepExecution($this->stepExecution);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->writer->setWaitTime($this->oldWaitTime);
    }

    public function testWrite()
    {
        /** @var Member $member1 */
        $member1 = $this->getReference('mailchimp:member_one');
        /** @var Member $member2 */
        $member2 = $this->getReference('mailchimp:member_two');
        /** @var Member $member3 */
        $member3 = $this->getReference('mailchimp:member_three');
        /** @var SubscribersList $subscribersList */
        $subscribersList = $this->getReference('mailchimp:subscribers_list_one');

        $this->transport->expects($this->atLeastOnce())->method('init');
        $this->transport->expects($this->atLeastOnce())
            ->method('getListMergeVars')
            ->with($subscribersList->getOriginId())
            ->willReturn([
                'merge_fields' => [
                    ['name' => 'email', 'tag' => 'EMAIL', 'type' => 'email', 'id' => 1],
                    ['name' => 'id', 'tag' => 'E_ID', 'type' => 'text', 'id' => 2],
                    ['name' => 'firstName', 'tag' => 'FIRSTNAME', 'type' => 'text', 'id' => 3],
                ],
            ]);
        $this->transport->expects($this->atLeastOnce())
            ->method('batchSubscribe')
            ->with([
                'list_id' => $subscribersList->getOriginId(),
                'members' => [
                    [
                        'email_address' => 'member1@example.com',
                        'status' => 'subscribed',
                        'merge_fields' => ['EMAIL' => 'member1@example.com', 'FIRSTNAME' => 'Antonio'],

                    ],
                    [
                        'email_address' => 'member2@example.com',
                        'status' => 'subscribed',
                        'merge_fields' => ['EMAIL' => 'member2@example.com', 'FIRSTNAME' => 'Michael'],
                    ],
                    [
                        'email_address' => 'member3@example.com',
                        'status' => 'subscribed',
                        'merge_fields' => ['EMAIL' => 'member3@example.com'],
                    ],
                ],
                'double_optin' => false,
                'update_existing' => true,
            ])
            ->willReturn([
                'total_created' => 0,
                'total_updated' => 2,
                'error_count' => 0,
                'new_members' => [
                    ['unique_email_id' => 1, 'id' => 2, 'email_address' => 'MEMBER1@example.com'],
                    ['unique_email_id' => 3, 'id' => 4, 'email_address' => 'member2@example.com'],
                ],
                'updated_members' => [
                    ['unique_email_id' => 5, 'id' => 6, 'email_address' => 'member3@example.com'],
                ]
            ]);

        $this->writer->write([$member1, $member2, $member3]);

        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(Member::class);
        $em->refresh($member1);
        $em->refresh($member2);
        $em->refresh($member3);

        $this->assertEquals([
            'EMAIL' => 'member1@example.com',
            'FIRSTNAME' => 'Antonio',
        ], $member1->getMergeVarValues());

        $this->assertEquals([
            'EMAIL' => 'member2@example.com',
            'FIRSTNAME' => 'Michael'
        ], $member2->getMergeVarValues());
        $this->assertEquals([
            'EMAIL' => 'member3@example.com'
        ], $member3->getMergeVarValues());

        $subscribersList = $em->find(SubscribersList::class, $subscribersList->getId());
        $this->assertEquals([
            ['name' => 'email', 'tag' => 'EMAIL', 'type' => 'email', 'id' => 1],
            ['name' => 'id', 'tag' => 'E_ID', 'type' => 'text', 'id' => 2],
            ['name' => 'firstName', 'tag' => 'FIRSTNAME', 'type' => 'text', 'id' => 3]
        ], $subscribersList->getMergeVarConfig());

        $this->assertEquals(Member::STATUS_SUBSCRIBED, $member1->getStatus());
        $this->assertEquals(1, $member1->getEuid());
        $this->assertEquals(Member::STATUS_SUBSCRIBED, $member2->getStatus());
        $this->assertEquals(3, $member2->getEuid());
        $this->assertEquals(Member::STATUS_SUBSCRIBED, $member3->getStatus());
        $this->assertEquals(5, $member3->getEuid());
    }
}
