<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Command;

use Oro\Bundle\MailChimpBundle\Async\Topics;
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

        $result = self::runCommand('oro:cron:mailchimp:export', ['--segments=' . $segment->getId()]);

        static::assertStringContainsString('Send export MailChimp message for integration:', $result);
        static::assertStringContainsString(
            'Integration "' . $segment->getChannel()->getId() . '" and segments "' . $segment->getId() . '"',
            $result
        );

        self::assertMessageSent(
            Topics::EXPORT_MAILCHIMP_SEGMENTS,
            [
                'integrationId' => $segment->getChannel()->getId(),
                'segmentsIds' => [$segment->getId()],
            ]
        );
        self::assertMessageSentWithPriority(Topics::EXPORT_MAILCHIMP_SEGMENTS, MessagePriority::VERY_LOW);
    }
}
