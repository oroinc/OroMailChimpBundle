<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Async;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Status;
use Oro\Bundle\MailChimpBundle\Async\ExportMailChimpProcessor;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ExportMailChimpProcessorTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    public function testCouldBeGetFromContainerAsService(): void
    {
        $processor = self::getContainer()->get('oro_mailchimp.async.export_mailchimp_processor');

        self::assertInstanceOf(ExportMailChimpProcessor::class, $processor);
    }

    public function testProcessIntegrationNotFound(): void
    {
        $sentMessage = self::sendMessage(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => PHP_INT_MAX,
                'userId' => $this->getUserId(true),
                'segmentsIds' => [PHP_INT_MAX],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_mailchimp.async.export_mailchimp_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains('The integration not found: ' . PHP_INT_MAX)
        );
    }

    public function testProcessIntegrationNotActive(): void
    {
        $integrationId = $this->getReference('mailchimp_transport:channel_disabled_1')->getId();

        $sentMessage = self::sendMessage(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => $integrationId,
                'userId' => $this->getUserId(true),
                'segmentsIds' => [PHP_INT_MAX],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_mailchimp.async.export_mailchimp_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains('The integration is not enabled: ' . $integrationId)
        );
    }

    public function testProcessUserNotFound(): void
    {
        $integrationId = $this->getReference('mailchimp:channel_1')->getId();
        $sentMessage = self::sendMessage(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => $integrationId,
                'userId' => PHP_INT_MAX,
                'segmentsIds' => [PHP_INT_MAX],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_mailchimp.async.export_mailchimp_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains('The user not found: ' . PHP_INT_MAX)
        );
    }

    public function testProcessUserIsNotActive(): void
    {
        $integrationId = $this->getReference('mailchimp:channel_1')->getId();
        $userId = $this->getUserId(false);
        $sentMessage = self::sendMessage(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => $integrationId,
                'userId' => $this->getUserId(false),
                'segmentsIds' => [PHP_INT_MAX],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_mailchimp.async.export_mailchimp_processor',
            $sentMessage
        );
        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains('The user is not enabled: ' . $userId)
        );
    }

    public function testProcess(): void
    {
        /** @var Channel $integration */
        $integration = $this->getReference('mailchimp:channel_1');
        $user = self::getContainer()->get('oro_user.manager')
            ->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        self::assertEmpty($integration->getStatuses());

        $sentMessage = self::sendMessage(
            ExportMailchimpSegmentsTopic::getName(),
            [
                'integrationId' => $integration->getId(),
                'userId' => $user->getId(),
                'segmentsIds' => [PHP_INT_MAX],
            ]
        );
        self::consumeMessage($sentMessage);

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertMessageSentWithPriority(ExportMailchimpSegmentsTopic::getName(), MessagePriority::VERY_LOW);
        self::assertProcessedMessageProcessor(
            'oro_mailchimp.async.export_mailchimp_processor',
            $sentMessage
        );

        $statuses = $integration->getStatuses();

        self::assertNotEmpty($statuses);

        /** @var Status $status */
        $status = $statuses->first();

        self::assertEquals($integration->getId(), $status->getChannel()->getId());
        self::assertEquals(Status::STATUS_COMPLETED, $status->getCode());
    }

    private function getUserId(bool $enabled): int
    {
        $reference = $enabled ? 'mailchimp:channel_1' : 'mailchimp_transport:channel_disabled_1';
        return $this->getReference($reference)->getDefaultUserOwner()->getId();
    }
}
