<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Command;

use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadStaticSegmentData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * @dbIsolationPerTest
 */
class MailChimpExportCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadStaticSegmentData::class]);
    }

    public function testShouldSendExportMailChimpSegmentsMessage(): void
    {
        /** @var StaticSegment $segment */
        $segment = $this->getReference('mailchimp:segment_one');
        $segmentId = $segment->getId();
        $integrationId = $segment->getChannel()->getId();
        $userId = $segment->getMarketingList()->getOwner()->getId();

        $result = self::runCommand('oro:cron:mailchimp:export', ['--segments=' . $segmentId]);

        self::assertStringContainsString('Send export MailChimp message for integration:', $result);
        self::assertStringContainsString(
            "Integration \"$integrationId\" user \"$userId\" and segments \"$segmentId\"",
            $result
        );

        self::assertMessageSent(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => $integrationId,
                'userId' => $userId,
                'segmentsIds' => [$segmentId],
            ]
        );
        self::assertMessageSentWithPriority(ExportMailchimpSegmentsTopic::getName(), MessagePriority::VERY_LOW);
    }
}
