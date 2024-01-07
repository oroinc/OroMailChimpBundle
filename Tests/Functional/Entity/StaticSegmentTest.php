<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Entity;

use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadMarketingListData;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadSubscribersListData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @dbIsolationPerTest
 */
class StaticSegmentTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');
        $this->loadFixtures([
            LoadMarketingListData::class,
            LoadSubscribersListData::class,
            LoadOrganization::class
        ]);
    }

    public function testShouldScheduleExportOnceStaticSegmentCreated(): void
    {
        $segment = new StaticSegment();
        $segment->setName('Test');
        $segment->setRemoteRemove(false);
        $segment->setSyncStatus(StaticSegment::STATUS_NOT_SYNCED);
        $segment->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
        $segment->setMarketingList($this->getReference('mailchimp:ml_one'));
        $segment->setSubscribersList($this->getReference('mailchimp:subscribers_list_one'));
        $segment->setChannel($this->getReference('mailchimp:channel_1'));

        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($segment);
        $em->flush();

        self::assertMessagesCount(ExportMailchimpSegmentsTopic::getName(), 1);
        self::assertMessageSent(ExportMailchimpSegmentsTopic::getName(), [
            'integrationId' => $segment->getChannel()->getId(),
            'segmentsIds' => [$segment->getId()]
        ]);
    }
}
