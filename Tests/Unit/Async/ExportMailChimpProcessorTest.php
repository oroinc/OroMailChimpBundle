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
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportMailChimpProcessorTest extends \PHPUnit\Framework\TestCase
{
    use IntegrationTokenAwareTestTrait;

    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        new ExportMailChimpProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->getReverseSyncProcessor(),
            $this->createMock(JobRunner::class),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class)
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

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The integration not found: ' . PHP_INT_MAX);

        $processor = new ExportMailChimpProcessor(
            $this->getDoctrineHelper(),
            $this->getReverseSyncProcessor(),
            $this->createMock(JobRunner::class),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldLogAndRejectIfIntegrationNotEnabled(): void
    {
        $integration = new Integration();
        $integration->setEnabled(false);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->getDoctrineHelper($integration);

        $message = new Message();
        $message->setBody([
            'integrationId' => 1,
            'segmentsIds' => [1, 2, 3],
        ]);
        $message->setMessageId('theMessageId');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The integration is not enabled: 1');

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $this->getReverseSyncProcessor(),
            $this->createMock(JobRunner::class),
            $this->createMock(TokenStorageInterface::class),
            $logger
        );

        $status = $processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @dataProvider processMessageDataProvider
     */
    public function testProcessMessageData(int $segmentId, array $segmentsIdsToSync, string $syncStatus): void
    {
        $integrationId = 1;
        $messageId = 'theMessageId';
        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $reverseSyncProcessor = $this->getReverseSyncProcessor();
        $reverseSyncProcessor->expects(self::exactly(2))
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
            $this->createMock(LoggerInterface::class)
        );

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

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $reverseSyncProcessor = $this->getReverseSyncProcessor();
        $reverseSyncProcessor->expects(self::once())
            ->method('process')
            ->with($integration, MemberConnector::TYPE, $expectedProcessorParameters)
            ->willReturn(false);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createMock(LoggerInterface::class)
        );

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

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $reverseSyncProcessor = $this->getReverseSyncProcessor();
        $reverseSyncProcessor->expects(self::exactly(2))
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
            $this->createMock(LoggerInterface::class)
        );

        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    private function getDoctrineHelper($integration = null): DoctrineHelper
    {
        $integrationEntityManager = $this->getIntegrationEntityManager($integration, $this->once());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);

        return $doctrineHelper;
    }

    private function getReverseSyncProcessor(): ReverseSyncProcessor|\PHPUnit\Framework\MockObject\MockObject
    {
        $reverseSyncProcessor = $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor->expects(self::any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $reverseSyncProcessor;
    }

    private function getEntityManager(): EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $configuration = new Configuration();

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);

        return $entityManager;
    }

    private function getIntegrationEntityManager(
        ?Integration $integration,
        InvokedCount $invokeCountMatcher
    ): EntityManagerInterface {
        $integrationEntityManager = $this->getEntityManager();
        $integrationEntityManager->expects($invokeCountMatcher)
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
        $segmentEntityManager->expects($invokeCountMatcher)
            ->method('persist')
            ->with($staticSegment);
        $segmentEntityManager->expects($invokeCountMatcher)
            ->method('flush');

        return $segmentEntityManager;
    }

    private function getJobRunner(string $messageId, string $integrationId): JobRunner
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @param  Message $message
     * @return JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertJobRunnerCalls(
        Message $message
    ): JobRunner|\PHPUnit\Framework\MockObject\MockObject {
        $jobRunner = $this->createJobRunnerMock();
        $jobRunner->expects(self::once())
            ->method('runUniqueByMessage')
            ->with($message, $this->isType('callable'))
            ->willReturnCallback(function ($message, $callback) {
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
        $staticSegment->expects(self::once())
            ->method('getSyncStatus')
            ->willReturn($syncStatus);
        $staticSegment->expects(self::exactly(count($segmentsIdsToSync) * 2))
            ->method('setSyncStatus')
            ->withConsecutive(...$expectedSegmentStatuses);
        $staticSegment->expects(self::exactly(count($segmentsIdsToSync)))
            ->method('setLastSynced')
            ->with($this->isInstanceOf(\DateTime::class));

        $staticSegmentRepository = $this->createMock(StaticSegmentRepository::class);
        $staticSegmentRepository->expects(self::exactly(1 + count($segmentsIdsToSync)))
            ->method('find')
            ->with($segmentId)
            ->willReturn($staticSegment);
        $doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(StaticSegment::class)
            ->willReturn($staticSegmentRepository);

        return $staticSegment;
    }

    private function assertEntityManagerCalls(
        StaticSegment $staticSegment,
        array $segmentsIdsToSync,
        Integration $integration,
        DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper
    ): void {
        $segmentEntityManager = $this
            ->getStaticSegmentEntityManager($staticSegment, $this->exactly(count($segmentsIdsToSync) * 4));
        $integrationEntityManager = $this->getIntegrationEntityManager($integration, $this->once());
        $doctrineHelper->expects(self::exactly(2))
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);
        $doctrineHelper->expects(self::exactly(count($segmentsIdsToSync) * 2))
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

    private function createJobRunnerMock()
    {
        return $this->createMock(JobRunner::class);
    }

    private function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
