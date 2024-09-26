<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Command;

use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;

class MailchimpImportMembersCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testShouldSendImportMailChimpMembersMessage(): void
    {
        /** @var Channel $channel */
        $channel = $this->getReference('mailchimp:channel_1');
        $result = self::runCommand('oro:mailchimp:force-import:members', ['--channel=' . $channel->getId()]);

        self::assertStringContainsString(
            sprintf('MailChimp member sync has been scheduled for integration ID "%s"', $channel->getId()),
            $result
        );
        self::assertMessageSent(
            SyncIntegrationTopic::getName(),
            [
                'integration_id' => $channel->getId(),
                'connector' => 'member',
                'connector_parameters' => ['force' => true]
            ]
        );
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::NORMAL);
    }
}
