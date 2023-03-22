<?php

namespace Oro\Bundle\MailChimpBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Job\Context\SelectiveContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Provider\Connector\MemberConnector;
use Oro\Bundle\MailChimpBundle\Provider\Connector\StaticSegmentConnector;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Mailchimp export routines message queue processor class.
 */
class ExportMailChimpProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    use IntegrationTokenAwareTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ReverseSyncProcessor
     */
    private $reverseSyncProcessor;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ReverseSyncProcessor $reverseSyncProcessor,
        JobRunner $jobRunner,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->reverseSyncProcessor = $reverseSyncProcessor;
        $this->jobRunner = $jobRunner;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->reverseSyncProcessor->getLoggerStrategy()->setLogger($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $messageBody['integrationId']);

        if (!$integration) {
            $this->logger->error(
                sprintf('The integration not found: %s', $messageBody['integrationId'])
            );

            return self::REJECT;
        }
        if (!$integration->isEnabled()) {
            $this->logger->error(
                sprintf('The integration is not enabled: %s', $messageBody['integrationId'])
            );

            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage($message, function () use ($messageBody, $integration) {
            $this->setTemporaryIntegrationToken($integration);

            return $this->processMessageData($messageBody, $integration);
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array $body
     * @param Integration $integration
     *
     * @return bool
     */
    protected function processMessageData(array $body, Integration $integration)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $segmentsIds = $body['segmentsIds'];
        /** @var StaticSegmentRepository $staticSegmentRepository */
        $staticSegmentRepository = $this->doctrineHelper->getEntityRepository(StaticSegment::class);

        $segmentsIdsToSync = [];
        $syncStatuses = [
            StaticSegment::STATUS_NOT_SYNCED,
            StaticSegment::STATUS_SCHEDULED,
            StaticSegment::STATUS_SCHEDULED_BY_CHANGE,
        ];
        foreach ($segmentsIds as $segmentId) {
            /** @var StaticSegment $staticSegment */
            $staticSegment = $staticSegmentRepository->find($segmentId);
            if ($staticSegment && in_array($staticSegment->getSyncStatus(), $syncStatuses, true)) {
                $this->setStaticSegmentStatus($staticSegment, StaticSegment::STATUS_IN_PROGRESS);
                $segmentsIdsToSync[] = $segmentId;
            }
        }

        $parameters = [
            'segments' => $segmentsIdsToSync,
            JobExecutor::JOB_CONTEXT_AGGREGATOR_TYPE => SelectiveContextAggregator::TYPE
        ];

        $isSynced = $this->reverseSyncProcessor->process($integration, MemberConnector::TYPE, $parameters);
        if ($isSynced) {
            $isSynced = $this->reverseSyncProcessor->process($integration, StaticSegmentConnector::TYPE, $parameters);
        }

        // reverse sync process does implicit entity manager clear, we have to re-query everything again.
        $status = $isSynced ? StaticSegment::STATUS_SYNCED : StaticSegment::STATUS_SYNC_FAILED;
        foreach ($segmentsIdsToSync as $segmentId) {
            /** @var StaticSegment $staticSegment */
            $staticSegment = $staticSegmentRepository->find($segmentId);

            if ($staticSegment) {
                $this->setStaticSegmentStatus($staticSegment, $status, true);
            }
        }

        return $isSynced;
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string $status
     * @param bool $lastSynced
     */
    protected function setStaticSegmentStatus(StaticSegment $staticSegment, $status, $lastSynced = false)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManager($staticSegment);

        $staticSegment->setSyncStatus($status);

        if ($lastSynced) {
            $staticSegment->setLastSynced(new \DateTime('now', new \DateTimeZone('UTC')));
        }

        $em->persist($staticSegment);
        $em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ExportMailchimpSegmentsTopic::getName()];
    }
}
