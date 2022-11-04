<?php
namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadMarketingListData;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadSubscribersListData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->loadFixtures([LoadMarketingListData::class, LoadSubscribersListData::class]);
    }

    public function testShouldScheduleExportOnceStaticSegmentCreated(): void
    {
        $organization = $this->getEntityManager()->getRepository(Organization::class)->getFirst();

        $segment = new StaticSegment();
        $segment->setName('Test');
        $segment->setRemoteRemove(false);
        $segment->setSyncStatus(StaticSegment::STATUS_NOT_SYNCED);
        $segment->setOwner($organization);
        $segment->setMarketingList($this->getReference('mailchimp:ml_one'));
        $segment->setSubscribersList($this->getReference('mailchimp:subscribers_list_one'));
        $segment->setChannel($this->getReference('mailchimp:channel_1'));

        $this->getEntityManager()->persist($segment);
        $this->getEntityManager()->flush();

        self::assertMessagesCount(ExportMailchimpSegmentsTopic::getName(), 1);
        self::assertMessageSent(ExportMailchimpSegmentsTopic::getName(), [
            'integrationId' => $segment->getChannel()->getId(),
            'segmentsIds' => [$segment->getId()],
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
