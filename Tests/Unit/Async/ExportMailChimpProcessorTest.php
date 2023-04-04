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
use Oro\Bundle\MailChimpBundle\Model\StaticSegment\StaticSegmentsMemberStateManager;
use Oro\Bundle\MailChimpBundle\Provider\Connector\MemberConnector;
use Oro\Bundle\MailChimpBundle\Provider\Connector\StaticSegmentConnector;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Oro\Component\Testing\ReflectionUtil;
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
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
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
        $integrationId = 1;
        $userId = 2;
        $messageId = 'theMessageId';

        $integration = new Integration();
        ReflectionUtil::setId($integration, $integrationId);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setId($userId);
        $user->setEnabled(true);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

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
            $this->createStaticSegmentsMemberStateManagerMock(),
            $jobRunner,
            $this->getTokenStorageMock(2, 2),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
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
        $integrationId = 2;

        $integration = new Integration();
        ReflectionUtil::setId($integration, $integrationId);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setId($userId);
        $user->setEnabled(true);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

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
        $reverseSyncProcessor = $this->createReverseSyncProcessorMock();
        $reverseSyncProcessor
            ->expects(self::once())
            ->method('process')
            ->with($integration, MemberConnector::TYPE, $expectedProcessorParameters)
            ->willReturn(false);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $this->createStaticSegmentsMemberStateManagerMock(),
            $jobRunner,
            $this->getTokenStorageMock(2, 2),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
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
        $integrationId = 1;

        $integration = new Integration();
        ReflectionUtil::setId($integration, $integrationId);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $user = new User();
        $user->setEnabled(true);
        $user->setId($userId);

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $jobRunner = $this->assertJobRunnerCalls($messageId, $integrationId);

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
            $this->createStaticSegmentsMemberStateManagerMock(),
            $jobRunner,
            $this->getTokenStorageMock(2, 2),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integration, $user, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    private function createReverseSyncProcessorMock(): ReverseSyncProcessor|\PHPUnit\Framework\MockObject\MockObject
    {
        $reverseSyncProcessor = $this->createMock(ReverseSyncProcessor::class);
        $reverseSyncProcessor
            ->expects(self::any())
            ->method('getLoggerStrategy')
            ->willReturn(new LoggerStrategy());

        return $reverseSyncProcessor;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|StaticSegmentsMemberStateManager
     */
    private function createStaticSegmentsMemberStateManagerMock()
    {
        return $this->createMock(StaticSegmentsMemberStateManager::class);
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
        User $user,
        DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper
    ): void {
        $segmentEntityManager = $this
            ->getStaticSegmentEntityManager($staticSegment, $this->exactly(count($segmentsIdsToSync) * 4));
        $integrationEntityManager = $this->createEntityManagerStub();
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
}
