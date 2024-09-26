<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegmentMember;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Psr\Log\LoggerInterface;

/**
 * Export batch job's mailchimp static segment writer.
 */
class StaticSegmentExportWriter extends AbstractExportWriter implements ContextAwareInterface
{
    const DROPPED_EMAILS_ERROR_CODE = 215;

    /**
     * Pay attention to the fact that the larger the batch, the longer it takes to fulfill the request.
     * If Mailchimp does not have time to return the result in a fixed time, the data will not be synchronized,
     * which will cause errors.
     * See: @Oro\Bundle\MailChimpBundle\Client\MailChimpClient::MAILCHIMP_MAX_TIMEOUT
     */
    private int $batchSize = 100;

    /**
     * @var string
     */
    protected $staticSegmentMemberClassName;

    /**
     * @var string
     */
    protected $memberClassName;

    /**
     * @var EntityRepository
     */
    protected $repository;

    /**
     * @var EntityManager
     */
    protected $manager;

    /**
     * @var ContextInterface
     */
    protected $context;

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution)
    {
        parent::setStepExecution($stepExecution);
        $this->setImportExportContext($this->contextRegistry->getByStepExecution($this->stepExecution));
    }

    /**
     * @param string $staticSegmentMemberClassName
     */
    public function setStaticSegmentMemberClassName($staticSegmentMemberClassName)
    {
        $this->staticSegmentMemberClassName = $staticSegmentMemberClassName;
    }

    /**
     * @param string $memberClassName
     */
    public function setMemberClassName($memberClassName)
    {
        $this->memberClassName = $memberClassName;
    }

    public function setBatchSize(int $batchSize): self
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * @param StaticSegment[] $items
     *
     */
    #[\Override]
    public function write(array $items)
    {
        $this->transport->init($items[0]->getChannel()->getTransport());

        foreach ($items as $staticSegment) {
            $this->addStaticListSegment($staticSegment);
            $this->handleMembersUpdate(
                $staticSegment,
                StaticSegmentMember::STATE_ADD,
                'addStaticSegmentMembers',
                StaticSegmentMember::STATE_SYNCED
            );

            $this->handleMembersUpdate(
                $staticSegment,
                StaticSegmentMember::STATE_REMOVE,
                'deleteStaticSegmentMembers',
                StaticSegmentMember::STATE_DROP
            );

            $this->handleMembersUpdate(
                $staticSegment,
                [StaticSegmentMember::STATE_UNSUBSCRIBE, StaticSegmentMember::STATE_UNSUBSCRIBE_DELETE],
                'deleteStaticSegmentMembers'
            );

            // Set unsubscribe to member
            $this->handleMembersUpdate(
                $staticSegment,
                StaticSegmentMember::STATE_UNSUBSCRIBE,
                'batchUnsubscribe',
                StaticSegmentMember::STATE_DROP,
                false,
                Member::STATUS_UNSUBSCRIBED
            );

            $this->handleMembersUpdate(
                $staticSegment,
                StaticSegmentMember::STATE_UNSUBSCRIBE_DELETE,
                'batchUnsubscribe',
                null,
                true
            );

            // Set "dropped" status to members which have been dropped from Mailchimp subscribers list.
            $this->handleMembersUpdate(
                $staticSegment,
                StaticSegmentMember::STATE_TO_DROP,
                null,
                StaticSegmentMember::STATE_DROP,
                false,
                Member::STATUS_DROPPED
            );
        }
    }

    /**
     * @throws \Exception
     */
    protected function addStaticListSegment(StaticSegment $staticSegment)
    {
        if (!$staticSegment->getOriginId()) {
            $response = $this->transport->addStaticListSegment([
                'list_id' => $staticSegment->getSubscribersList()->getOriginId(),
                'name' => $staticSegment->getName(),
            ]);

            if (!empty($response['id'])) {
                $staticSegment->setOriginId($response['id']);

                $this->logger->debug(sprintf('StaticSegment with id "%s" added', $staticSegment->getOriginId()));

                parent::write([$staticSegment]);
                $this->context->incrementAddCount();
            }
        }
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string|array $segmentStateFilter
     * @param string $method
     * @param string|null $itemState
     * @param bool $deleteMember
     * @param string|null $memberStatus
     */
    public function handleMembersUpdate(
        StaticSegment $staticSegment,
        $segmentStateFilter,
        $method,
        $itemState = null,
        $deleteMember = false,
        $memberStatus = null
    ) {
        $emailsIterator = $this->getSegmentMembersEmailsIterator($staticSegment, $segmentStateFilter);
        if (!$emailsIterator->count()) {
            return;
        }

        $emailsToProcess = [];
        $emailsIterator->rewind();
        while ($emailsIterator->valid()) {
            $data = $emailsIterator->current();
            $emailsToProcess[$data['staticSegmentMemberId']] = $data['memberEmail'];
            $emailsIterator->next();

            if (count($emailsToProcess) % $this->batchSize === 0 || (!$emailsIterator->valid() && $emailsToProcess)) {
                $this->handleEmailsBatch(
                    $staticSegment,
                    $method,
                    $emailsToProcess,
                    $itemState,
                    $deleteMember,
                    $memberStatus
                );

                $emailsToProcess = [];
            }
        }

        if ($deleteMember) {
            $this->deleteListMembers($staticSegment, $segmentStateFilter);
        }
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string $method
     * @param array $emailsToProcess
     * @param string|null $itemState
     * @param bool $deleteMember
     * @param string|null $memberStatus
     */
    protected function handleEmailsBatch(
        StaticSegment $staticSegment,
        $method,
        array $emailsToProcess,
        $itemState = null,
        $deleteMember = false,
        $memberStatus = null
    ) {
        $response = [];
        if ($method !== null) {
            $response = $this->makeRequest($staticSegment, $method, $emailsToProcess, $deleteMember);
        }

        $emailsToUpdate = array_diff($emailsToProcess, $this->getEmailsWithErrors($response));

        if ($emailsToUpdate) {
            $this->updateStaticSegmentMembersState($emailsToUpdate, $itemState);

            if ($memberStatus) {
                $this->updateMembersStatus(
                    $staticSegment->getSubscribersList(),
                    $emailsToProcess,
                    $memberStatus
                );
            }
        }
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string|array $state
     *
     * @return BufferedQueryResultIteratorInterface
     */
    protected function getSegmentMembersEmailsIterator(StaticSegment $staticSegment, $state)
    {
        $qb = $this->getSegmentMembersQueryBuilder($staticSegment, $state);

        $qb->select('staticSegmentMember.id as staticSegmentMemberId, mmbr.email as memberEmail')
            ->leftJoin('staticSegmentMember.member', 'mmbr');

        $iterator = new BufferedIdentityQueryResultIterator($qb);
        $iterator->setBufferSize($this->batchSize);

        return $iterator;
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string|array $state
     *
     * @return QueryBuilder
     */
    protected function getSegmentMembersQueryBuilder(StaticSegment $staticSegment, $state)
    {
        $qb = $this->getRepository()->createQueryBuilder('staticSegmentMember');

        if (is_array($state)) {
            $stateExpr = $qb->expr()->in('staticSegmentMember.state', ':state');
        } else {
            $stateExpr = $qb->expr()->eq('staticSegmentMember.state', ':state');
        }

        $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('staticSegmentMember.staticSegment', ':staticSegment'),
                    $stateExpr
                )
            )
            ->setParameter('staticSegment', $staticSegment)
            ->setParameter('state', $state);

        return $qb;
    }

    /**
     * @param array $emailsToUpdate
     * @param string|null $itemState
     */
    protected function updateStaticSegmentMembersState($emailsToUpdate, $itemState)
    {
        if ($itemState) {
            /** @var QueryBuilder $qb */
            $qb = $this->getManager()->createQueryBuilder();
            $qb->update($this->staticSegmentMemberClassName, 'staticSegmentMember')
                ->set('staticSegmentMember.state', ':state')
                ->setParameter('state', $itemState)
                ->where($qb->expr()->in('staticSegmentMember.id', ':ids'))
                ->setParameter('ids', array_keys($emailsToUpdate))
                ->getQuery()
                ->execute();

            foreach ($emailsToUpdate as $id => $email) {
                $this->logger->debug(
                    sprintf(
                        'Member with id "%s" and email "%s" got "%s" state',
                        $id,
                        $email,
                        $itemState
                    )
                );
            }
        }
    }

    /**
     * @param SubscribersList $subscribersList
     * @param array $emailsToUpdate
     * @param string $memberStatus
     */
    protected function updateMembersStatus(SubscribersList $subscribersList, $emailsToUpdate, $memberStatus)
    {
        $qb = $this->getManager()->createQueryBuilder();
        $qb->update($this->memberClassName, 'mmb')
            ->set('mmb.status', ':status')
            ->setParameter('status', $memberStatus)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('mmb.subscribersList', ':subscribersList'),
                    $qb->expr()->in('mmb.email', ':emails')
                )
            )
            ->setParameter('subscribersList', $subscribersList)
            ->setParameter('emails', array_values($emailsToUpdate))
            ->getQuery()
            ->execute();
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string|array $state
     */
    protected function deleteListMembers(StaticSegment $staticSegment, $state)
    {
        $qb = $this->getSegmentMembersQueryBuilder($staticSegment, $state);
        $qb->select('IDENTITY(staticSegmentMember.member)');

        $deleteQb = $this->getManager()->createQueryBuilder();
        $deleteQb->delete($this->memberClassName, 'listMember')
            ->where(
                $deleteQb->expr()->in('listMember.id', $qb->getDQL())
            )
            ->setParameters($qb->getParameters());

        $deleteQb->getQuery()->execute();
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        if (!$this->staticSegmentMemberClassName) {
            throw new \InvalidArgumentException('Missing StaticSegmentMember class name');
        }

        if (!$this->repository) {
            $this->repository = $this->getManager()->getRepository($this->staticSegmentMemberClassName);
        }

        return $this->repository;
    }

    /**
     * @return EntityManager
     */
    protected function getManager()
    {
        if (!$this->manager) {
            $this->manager = $this->registry->getManager();
        }

        return $this->manager;
    }

    /**
     * @param array $response
     * @return array
     */
    protected function getEmailsWithErrors(array $response)
    {
        return array_map(
            function ($item) {
                return $item['email'];
            },
            $this->getArrayData($response, 'errors', 'email')
        );
    }

    protected function buildBatchParams(StaticSegment $staticSegment, $method, array $emailsToProcess, $deleteMember)
    {
        $batchParameters = [
            'id' => $staticSegment->getSubscribersList()->getOriginId(),
        ];

        if ($method === 'addStaticSegmentMembers') {
            $batchParameters['members_to_add'] = array_values($emailsToProcess);
        }

        if ($method === 'deleteStaticSegmentMembers') {
            $batchParameters['members_to_remove'] = array_values($emailsToProcess);
        }

        if ($method === 'batchUnsubscribe') {
            $batchParameters['members'] = array_map(
                function ($email) {
                    return [
                        'email_address' => $email,
                        'status' => 'unsubscribed',
                    ];
                },
                array_values($emailsToProcess)
            );

            $batchParameters['update_existing'] = true;
        }

        if ($method === 'addStaticSegmentMembers' || $method === 'deleteStaticSegmentMembers') {
            $batchParameters['static_segment_id'] = (int)$staticSegment->getOriginId();
        }

        if ($deleteMember) {
            $batchParameters['delete_member'] = true;
        }
        return $batchParameters;
    }

    /**
     * @param StaticSegment $staticSegment
     * @param string $method
     * @param array $emailsToProcess
     * @param bool $deleteMember
     *
     * @return array
     */
    protected function makeRequest(StaticSegment $staticSegment, $method, array $emailsToProcess, $deleteMember)
    {
        $batchParameters = $this->buildBatchParams($staticSegment, $method, $emailsToProcess, $deleteMember);

        $response = $this->transport->$method($batchParameters);

        $this->handleResponse(
            $response,
            function ($response, LoggerInterface $logger) use ($staticSegment, $method, $batchParameters) {
                $total = 0;
                $error = 0;
                if (isset($response['total_created'])) {
                    $total = $response['total_created'];
                } elseif (isset($response['total_added'])) {
                    $total = $response['total_added'];
                }

                if (isset($response['error_count'])) {
                    $error = $response['error_count'];
                }

                $logger->info(
                    sprintf(
                        'Segment #%s [origin_id=%s] Members: [%s] add, [%s] error',
                        $staticSegment->getId(),
                        $staticSegment->getOriginId(),
                        $total,
                        $error
                    )
                );

                if (!empty($response['errors']) && is_array($response['errors'])) {
                    $logger->notice(
                        'Mailchimp error occurs during execution "{method}" method for ' .
                        'static segment "{static_segment_name}" (id: {static_segment_id})',
                        [
                            'method' => $method,
                            'static_segment_name' => $staticSegment->getName(),
                            'static_segment_id' => $staticSegment->getId(),
                        ]
                    );

                    $logger->debug(
                        'Mailchimp error occurs during execution "{method}" method for ' .
                        'static segment "{static_segment_name}" (id: {static_segment_id})',
                        [
                            'method' => $method,
                            'static_segment_name' => $staticSegment->getName(),
                            'static_segment_id' => $staticSegment->getId(),
                            'batch_parameters' => $batchParameters,
                            'response' => $response
                        ]
                    );
                }
            }
        );

        return $response;
    }
}
