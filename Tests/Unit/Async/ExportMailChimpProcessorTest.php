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
use Oro\Bundle\MailChimpBundle\Async\ExportMailChimpProcessor;
use Oro\Bundle\MailChimpBundle\Async\Topics;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Model\StaticSegment\StaticSegmentsMemberStateManager;
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

    public function testShouldImplementMessageProcessorInterface()
    {
        self::assertClassImplements(MessageProcessorInterface::class, ExportMailChimpProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        self::assertClassImplements(TopicSubscriberInterface::class, ExportMailChimpProcessor::class);
    }

    public function testCouldBeConstructedWithExpectedArguments()
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

    public function testShouldSubscribeOnExportMailChimpSegmentsTopic()
    {
        $this->assertEquals([Topics::EXPORT_MAILCHIMP_SEGMENTS], ExportMailChimpProcessor::getSubscribedTopics());
    }

    public function testShouldLogAndRejectIfMessageBodyMissIntegrationId()
    {
        $message = new Message();
        $message->setBody('[]');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The message invalid. It must have integrationId set');

        $processor = new ExportMailChimpProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->createReverseSyncProcessorMock(),
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldLogAndRejectIfMessageBodyMissSegmentsIds()
    {
        $message = new Message();
        $message->setBody('{"integrationId":1}');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The message invalid. It must have segmentsIds set');

        $processor = new ExportMailChimpProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->createReverseSyncProcessorMock(),
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testThrowIfMessageBodyInvalidJson()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');

        $processor = new ExportMailChimpProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->createReverseSyncProcessorMock(),
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = new Message();
        $message->setBody('[}');

        $processor->process($message, $this->createSessionMock());
    }

    public function testShouldLogAndRejectIfIntegrationNotFound()
    {
        $message = new Message();
        $message->setBody('{"integrationId":"theIntegrationId", "segmentsIds": 1}');
        $message->setMessageId('theMessageId');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration not found: theIntegrationId');

        $processor = new ExportMailChimpProcessor(
            $this->createDoctrineHelperStub(),
            $this->createReverseSyncProcessorMock(),
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldLogAndRejectIfIntegrationNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);
        $integration->setOrganization(new Organization());

        $doctrineHelper = $this->createDoctrineHelperStub($integration);

        $message = new Message();
        $message->setBody('{"integrationId":"theIntegrationId", "segmentsIds": 1}');
        $message->setMessageId('theMessageId');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration is not enabled: theIntegrationId');

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $this->createReverseSyncProcessorMock(),
            $this->createStaticSegmentsMemberStateManagerMock(),
            $this->createJobRunnerMock(),
            $this->createTokenStorageMock(),
            $logger
        );

        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @dataProvider processMessageDataProvider
     */
    public function testProcessMessageData(int $segmentId, array $segmentsIdsToSync, string $syncStatus)
    {
        $integrationId = 'theIntegrationId';
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
            ->expects($this->exactly(2))
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
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @return array
     */
    public function processMessageDataProvider()
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

    public function testProcessMessageDataMemberConnectorFail()
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
            ->expects($this->once())
            ->method('process')
            ->with($integration, MemberConnector::TYPE, $expectedProcessorParameters)
            ->willReturn(false);

        $processor = new ExportMailChimpProcessor(
            $doctrineHelper,
            $reverseSyncProcessor,
            $this->createStaticSegmentsMemberStateManagerMock(),
            $jobRunner,
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testProcessMessageDataSegmentConnectorFail()
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
            ->expects($this->exactly(2))
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
            $this->createTokenStorageMock(),
            $this->createLoggerMock()
        );

        $message = $this->getMessage($integrationId, $segmentId, $messageId);
        $status = $processor->process($message, $this->createSessionMock());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($integration = null)
    {
        $integrationEntityManager = $this->getIntegrationEntityManager($integration, $this->once());

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->expects($this->any())
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
            ->expects($this->any())
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
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
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

    /**
     * @param StaticSegment $staticSegment
     * @param \PHPUnit\Framework\MockObject\Rule\InvokedCount $invokeCountMatcher
     *
     * @return EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getStaticSegmentEntityManager(
        StaticSegment $staticSegment,
        InvokedCount $invokeCountMatcher
    ) {
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
        $jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($messageId, 'oro_mailchimp:export_mailchimp:' . $integrationId, $this->isType('callable'))
            ->willReturnCallback(function ($ownerId, $jobName, $callback) {
                return $callback();
            });

        return $jobRunner;
    }

    /**
     * @param string $syncStatus
     * @param int $segmentId
     * @param array $segmentsIdsToSync
     * @param array $expectedSegmentStatuses
     * @param DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper
     * @return StaticSegment|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertSegmentStatusChanges(
        string $syncStatus,
        int $segmentId,
        array $segmentsIdsToSync,
        array $expectedSegmentStatuses,
        DoctrineHelper $doctrineHelper
    ): StaticSegment|\PHPUnit\Framework\MockObject\MockObject {
        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment
            ->expects($this->once())
            ->method('getSyncStatus')
            ->willReturn($syncStatus);

        $staticSegment
            ->expects($this->exactly(count($segmentsIdsToSync) * 2))
            ->method('setSyncStatus')
            ->withConsecutive(
                ...$expectedSegmentStatuses
            );

        $staticSegment
            ->expects($this->exactly(count($segmentsIdsToSync)))
            ->method('setLastSynced')
            ->with($this->isInstanceOf(\DateTime::class));

        $staticSegmentRepository = $this->createMock(StaticSegmentRepository::class);
        $staticSegmentRepository
            ->expects($this->exactly(1 + count($segmentsIdsToSync)))
            ->method('find')
            ->with($segmentId)
            ->willReturn($staticSegment);
        $doctrineHelper
            ->expects($this->once())
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
            ->expects($this->exactly(2))
            ->method('getEntityManagerForClass')
            ->with(Integration::class)
            ->willReturn($integrationEntityManager);
        $doctrineHelper
            ->expects($this->exactly(count($segmentsIdsToSync) * 2))
            ->method('getEntityManager')
            ->willReturn($segmentEntityManager);
    }

    /**
     * @param string $integrationId
     * @param int $segmentId
     * @param string $messageId
     * @return Message
     */
    private function getMessage(string $integrationId, int $segmentId, string $messageId): Message
    {
        $message = new Message();
        $message->setBody('{"integrationId":"' . $integrationId . '", "segmentsIds": [' . $segmentId . ']}');
        $message->setMessageId($messageId);

        return $message;
    }
}
