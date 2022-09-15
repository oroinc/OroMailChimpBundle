<?php
namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Transport;

use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadStaticSegmentData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Client\MessagePriority;

class SyncTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadStaticSegmentData::class]);
    }

    /**
     * @dataProvider provideConnectorName
     */
    public function testSyncCampaign($connectorName): void
    {
        $params = ['--integration' => '1', '--connector' => $connectorName];
        $params['--integration'] = (string)$this->getReference(
            'mailchimp:channel_' . $params['--integration']
        )->getId();
        $result = self::runCommand('oro:cron:integration:sync', $params);

        static::assertStringContainsString('Schedule sync for "mailchimp1" integration.', $result);

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $params['--integration'],
            'connector_parameters' => [],
            'connector' => $connectorName,
            'transport_batch_size' => 100
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    public function provideConnectorName(): array
    {
        return [
            ['campaign'],
            ['list'],
            ['member_activity'],
            ['member'],
            ['static_segment']
        ];
    }
}
