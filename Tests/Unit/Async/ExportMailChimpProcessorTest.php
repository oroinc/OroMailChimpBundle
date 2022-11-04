<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token\IntegrationTokenAwareTestTrait;
use Oro\Bundle\MailChimpBundle\Async\ExportMailChimpProcessor;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Provider\Connector\MemberConnector;
use Oro\Bundle\MailChimpBundle\Provider\Connector\StaticSegmentConnector;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportMailChimpProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use IntegrationTokenAwareTestTrait;

    public function testShouldImplementMessageProcessorInterface(): void
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExportMailChimpProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface(): void
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExportMailChimpProcessor::class);
    }

    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        new ExportMailChimpProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->createReverseSyncProcessorMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldSubscribeOnExportMailChimpSegmentsTopic(): void
    {
        self::assertEquals([ExportMailchimpSegmentsTopic::getName()], ExportMailChimpProcessor::getSubscribedTopics());
    }

    public function testShouldLogAndRejectIfIntegrationNotFound(): void
    {
        $message = new Message();
        $message->setBody([
            'integrationId' => PHP_INT_MAX,
            'segmentsIds' => [1, 2, 3],
        ]);
        $message->setMessageId('theMessageId');

        $logger = $this->createLoggerMock();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('The integration not found: ' . PHP_INT_MAX);

        $processor = new ExportMailChimpProcessor(
            $this->createDoctrineHelperStub(),
            $this->createReverseSyncProcessorMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldLogAndRejectIfIntegrationNotEnabled(): void
    {
        $integration = new Integration();
        $integration->setEnabled(false);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->createDoctrineHelperStub($integration);

        $message = new Message();
        $message->setBody([
            'integrationId' => 1,
            'segmentsIds' => [1, 2, 3],
        ]);
        $message->setMessageId('theMessageId');

        $logger = $this->createLoggerMock();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('The integration is not enabled: 1');

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $this->createReverseSyncProcessorMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @dataProvider processMessageDataProvider
     */
    public function testProcessMessageData(int $segmentId, array $segmentsIdsToSync, string $syncStatus): void
    {
        $integrationId = 1;
        $messageId = 'theMessageId';

        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $expectedSegmentStatuses = [
            [StaticSegment::STATUS_IN_PROGRESS],
            [StaticSegment::STATUS_SYNCED]
        ];
        $staticSegment = $this->assertSegmentStatusChanges(
            $syncStatus,
            $segmentId,
            $segmentsIdsToSync,
            $expectedSegmentStatuses,
            $doctrineHelper
        );
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $doctrineHelper);

        $expectedProcessorParameters = [
            'segments' => $segmentsIdsToSync,
            JobExecutor::JOB_CONTEXT_AGGREGATOR_TYPE => SelectiveContextAggregator::TYPE,
        ];
        $reverseSyncProcessor = $this->createReverseSyncProcessorMock();
        $reverseSyncProcessor
            ->expects(self::exactly(2))
            ->method('process')
            ->withConsecutive(
                [$integration, MemberConnector::TYPE, $expectedProcessorParameters],
                [$integration, StaticSegmentConnector::TYPE, $expectedProcessorParameters]
            )
            ->willReturn(true);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function processMessageDataProvider(): array
    {
        return [
            'static segment is in not_synced status' => [
                'segmentId' => 1,
                'segmentIds' => [1],
                'syncStatus' => StaticSegment::STATUS_NOT_SYNCED,
            ],
            'static segment is in scheduled status' => [
                'segmentId' => 1,
                'segmentIds' => [1],
                'syncStatus' => StaticSegment::STATUS_SCHEDULED,
            ],
            'static segment is in scheduled_by_change status' => [
                'segmentId' => 1,
                'segmentIds' => [1],
                'syncStatus' => StaticSegment::STATUS_SCHEDULED_BY_CHANGE,
            ],
            'static segment is in synced status' => [
                'segmentId' => 1,
                'segmentIds' => [],
                'syncStatus' => StaticSegment::STATUS_SYNCED,
            ],
            'static segment is in sync failed status' => [
                'segmentId' => 1,
                'segmentIds' => [],
                'syncStatus' => StaticSegment::STATUS_SYNC_FAILED,
            ],
        ];
    }

    public function testProcessMessageDataMemberConnectorFail(): void
    {
        $segmentId = 1;
        $segmentsIdsToSync = [1];
        $syncStatus = StaticSegment::STATUS_SCHEDULED;
        $integrationId = 'theIntegrationId';
        $messageId = 'theMessageId';

        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $expectedSegmentStatuses = [
            [StaticSegment::STATUS_IN_PROGRESS],
            [StaticSegment::STATUS_SYNC_FAILED]
        ];
        $staticSegment = $this->assertSegmentStatusChanges(
            $syncStatus,
            $segmentId,
            $segmentsIdsToSync,
            $expectedSegmentStatuses,
            $doctrineHelper
        );
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $doctrineHelper);

        $expectedProcessorParameters = [
            'segments' => $segmentsIdsToSync,
            JobExecutor::JOB_CONTEXT_AGGREGATOR_TYPE => SelectiveContextAggregator::TYPE,
        ];
        $reverseSyncProcessor = $this->createReverseSyncProcessorMock();
        $reverseSyncProcessor
            ->expects(self::once())
            ->method('process')
            ->with($integration, MemberConnector::TYPE, $expectedProcessorParameters)
            ->willReturn(false);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testProcessMessageDataSegmentConnectorFail(): void
    {
        $segmentId = 1;
        $segmentsIdsToSync = [1];
        $syncStatus = StaticSegment::STATUS_SCHEDULED;
        $integrationId = 'theIntegrationId';
        $messageId = 'theMessageId';

        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $expectedSegmentStatuses = [
            [StaticSegment::STATUS_IN_PROGRESS],
            [StaticSegment::STATUS_SYNC_FAILED]
        ];
        $staticSegment = $this->assertSegmentStatusChanges(
            $syncStatus,
            $segmentId,
            $segmentsIdsToSync,
            $expectedSegmentStatuses,
            $doctrineHelper
        );
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $doctrineHelper);

        $expectedProcessorParameters = [
            'segments' => $segmentsIdsToSync,
            JobExecutor::JOB_CONTEXT_AGGREGATOR_TYPE => SelectiveContextAggregator::TYPE,
        ];
        $reverseSyncProcessor = $this->createReverseSyncProcessorMock();
        $reverseSyncProcessor
            ->expects(self::exactly(2))
            ->method('process')
            ->withConsecutive(
                [$integration, MemberConnector::TYPE, $expectedProcessorParameters],
                [$integration, StaticSegmentConnector::TYPE, $expectedProcessorParameters]
            )
            ->willReturn(true, false);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($integration = null)
    {
        $integrationEntityManager = $this->getIntegrationEntityManager($integration, $this->once());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);

        return $doctrineHelper;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ReverseSyncProcessor
     */
    private function createReverseSyncProcessorMock()
    {
        $reverseSyncProcessor = $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor
            ->expects(self::any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $reverseSyncProcessor;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobRunner
     */
    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @param Integration|null $integration
     * @param InvokedCount $invokeCountMatcher
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getIntegrationEntityManager(
        ?Integration $integration,
        InvokedCount $invokeCountMatcher
    ) {
        $integrationEntityManager = $this->createEntityManagerStub();
        $integrationEntityManager
            ->expects($invokeCountMatcher)
            ->method('find')
            ->with(Integration::class)
            ->willReturn($integration);

        return $integrationEntityManager;
    }

    private function getStaticSegmentEntityManager(
        StaticSegment $staticSegment,
        InvokedCount $invokeCountMatcher
    ):EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject {
        $segmentEntityManager = $this->createMock(EntityManagerInterface::class);
        $segmentEntityManager
            ->expects($invokeCountMatcher)
            ->method('persist')
            ->with($staticSegment);
        $segmentEntityManager
            ->expects($invokeCountMatcher)
            ->method('flush');

        return $segmentEntityManager;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @param string $messageId
     * @param string $integrationId
     * @return JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertJobRunnerCalls(
        string $messageId,
        string $integrationId
    ): JobRunner|\PHPUnit\Framework\MockObject\MockObject {
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner->expects(self::once())
            ->method('runUnique')
            ->with($messageId, 'oro_mailchimp:export_mailchimp:' . $integrationId, $this->isType('callable'))
            ->willReturnCallback(function ($ownerId, $jobName, $callback) {
                return $callback();
            });

        return $jobRunner;
    }

    private function assertSegmentStatusChanges(
        string $syncStatus,
        int $segmentId,
        array $segmentsIdsToSync,
        array $expectedSegmentStatuses,
        DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper
    ): StaticSegment|\PHPUnit\Framework\MockObject\MockObject {
        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment
            ->expects(self::once())
            ->method('getSyncStatus')
            ->willReturn($syncStatus);

        $staticSegment
            ->expects(self::exactly(count($segmentsIdsToSync) * 2))
            ->method('setSyncStatus')
            ->withConsecutive(
                ...$expectedSegmentStatuses
            );

        $staticSegment
            ->expects(self::exactly(count($segmentsIdsToSync)))
            ->method('setLastSynced')
            ->with($this->isInstanceOf(\DateTime::class));

        $staticSegmentRepository = $this->createMock(StaticSegmentRepository::class);
        $staticSegmentRepository
            ->expects(self::exactly(1 + count($segmentsIdsToSync)))
            ->method('find')
            ->with($segmentId)
            ->willReturn($staticSegment);
        $doctrineHelper
            ->expects(self::once())
            ->method('getEntityRepository')
            ->with(StaticSegment::class)
            ->willReturn($staticSegmentRepository);

        return $staticSegment;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\MockObject|StaticSegment $staticSegment
     * @param array $segmentsIdsToSync
     * @param Integration $integration
     * @param \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper $doctrineHelper
     */
    private function assertEntityManagerCalls(
        \PHPUnit\Framework\MockObject\MockObject|StaticSegment $staticSegment,
        array $segmentsIdsToSync,
        Integration $integration,
        \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper $doctrineHelper
    ): void {
        $segmentEntityManager = $this
            ->getStaticSegmentEntityManager($staticSegment, $this->exactly(count($segmentsIdsToSync) * 4));
        $integrationEntityManager = $this->getIntegrationEntityManager($integration, $this->once());
        $doctrineHelper
            ->expects(self::exactly(2))
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);
        $doctrineHelper
            ->expects(self::exactly(count($segmentsIdsToSync) * 2))
            ->method('getEntityManager')
            ->willReturn($segmentEntityManager);
    }

    private function getMessage(string $integrationId, int $segmentId, string $messageId): Message
    {
        $message = new Message();
        $message->setBody([
            'integrationId' => $integrationId,
            'segmentsIds' => [$segmentId],
        ]);
        $message->setMessageId($messageId);

        return $message;
    }
}
