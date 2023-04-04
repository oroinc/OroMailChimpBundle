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
use Oro\Bundle\UserBundle\Entity\User;
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

    /**
     * @dataProvider processMessageDataProvider
     */
    public function testProcessMessageData(int $segmentId, array $segmentsIdsToSync, string $syncStatus): void
    {
        $userId = 2;
        $messageId = 'theMessageId';

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setId($userId);
        $user->setEnabled(true);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $user, $doctrineHelper);

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
            $this->getTokenStorageMock(2, 2),
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
        $userId = 1;
        $messageId = 'theMessageId';

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setId($userId);
        $user->setEnabled(true);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $user, $doctrineHelper);

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
            $this->getTokenStorageMock(2, 2),
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
        $userId = 2;
        $messageId = 'theMessageId';

        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setEnabled(true);
        $user->setId($userId);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($message);

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
        $this->assertEntityManagerCalls($staticSegment, $segmentsIdsToSync, $integration, $user, $doctrineHelper);

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
            $this->getTokenStorageMock(2, 2),
            $this->createMock(LoggerInterface::class)
        );

        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
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
        User $user,
        DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper
    ): void {
        $segmentEntityManager = $this
            ->getStaticSegmentEntityManager($staticSegment, $this->exactly(count($segmentsIdsToSync) * 4));
        $integrationEntityManager = $this->getEntityManager();
        $doctrineHelper->expects(self::once())
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);
        $doctrineHelper->expects(self::exactly(count($segmentsIdsToSync) * 2))
            ->method('getEntityManager')
            ->willReturn($segmentEntityManager);
    }

    private function getMessage(Integration $integration, User $user, int $segmentId, string $messageId): Message
    {
        $message = new Message();
        $message->setBody([
            'integrationId' => $integration,
            'userId' => $user,
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
