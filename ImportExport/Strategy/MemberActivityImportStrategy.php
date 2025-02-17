<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Strategy;

use Doctrine\ORM\AbstractQuery;
use Monolog\Logger;
use Oro\Bundle\ImportExportBundle\Strategy\Import\AbstractImportStrategy as BasicImportStrategy;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\ImportExport\Helper\DefaultOwnerHelper;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;
use Oro\Bundle\MailChimpBundle\Provider\Connector\MemberActivityConnector;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Mailchimp member activity export strategy.
 */
class MemberActivityImportStrategy extends BasicImportStrategy implements LoggerAwareInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected int $logLevel = Logger::INFO;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var DefaultOwnerHelper
     */
    protected $ownerHelper;

    /**
     * @var array
     */
    protected $singleInstanceActivities = [
        MemberActivity::ACTIVITY_SENT,
        MemberActivity::ACTIVITY_UNSUB,
        MemberActivity::ACTIVITY_BOUNCE
    ];

    #[\Override]
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setLogLevel(int $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param MemberActivity $entity
     * @return MemberActivity
     */
    #[\Override]
    public function process($entity)
    {
        $this->assertEnvironment($entity);

        $entity = $this->beforeProcessEntity($entity);
        $entity = $this->processEntity($entity);
        $entity = $this->afterProcessEntity($entity);

        return $entity;
    }

    /**
     * Member Activity is added only for known member.
     * There can not be two send activities in campaign for same member.
     *
     * @param MemberActivity $entity
     * @return MemberActivity
     */
    protected function processEntity($entity)
    {
        $this->logger?->log(
            $this->logLevel,
            sprintf(
                'Processing MailChimp Member Activity [email=%s, action=%s]',
                $entity->getEmail(),
                $entity->getAction()
            )
        );

        /** @var Channel $channel */
        $channel = $this->databaseHelper->getEntityReference($entity->getChannel());
        /** @var Campaign $campaign */
        $campaign = $this->databaseHelper->getEntityReference($entity->getCampaign());
        $member = $this->findExistingMember($entity, $channel, $campaign);

        $entity
            ->setChannel($channel)
            ->setCampaign($campaign)
            ->setMember($member);

        if ($member && !$this->isSkipped($entity)) {
            if (!$entity->getActivityTime()) {
                $entity->setActivityTime($campaign->getSendTime());
            }
            $this->context->incrementAddCount();

            $this->logger?->log(
                $this->logLevel,
                sprintf(
                    '    Activity added for MailChimp Member [id=%d]',
                    $member->getId()
                )
            );

            return $entity;
        } else {
            $this->logger?->log($this->logLevel, '    Activity skipped');
        }

        return null;
    }

    /**
     * @param MemberActivity $entity
     * @return MemberActivity
     */
    #[\Override]
    protected function afterProcessEntity($entity)
    {
        if (!$entity) {
            return null;
        }

        $validationErrors = $this->strategyHelper->validateEntity($entity);
        if ($validationErrors) {
            $this->context->incrementErrorEntriesCount();
            $this->strategyHelper->addValidationErrors($validationErrors, $this->context);

            return null;
        }

        $this->setOwner($entity);

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param MemberActivity $memberActivity
     * @param Channel $channel
     * @param Campaign $campaign
     * @return Member|null
     */
    protected function findExistingMember(MemberActivity $memberActivity, Channel $channel, Campaign $campaign)
    {
        $searchCondition = [
            'channel' => $channel,
            'subscribersList' => $campaign->getSubscribersList(),
        ];

        $member = $memberActivity->getMember();
        $email = $member ? $member->getEmail() : $memberActivity->getEmail();
        if ($member && $originId = $member->getOriginId()) {
            $searchCondition['originId'] = $originId;
        } elseif ($email) {
            $searchCondition['email'] = $email;
        } else {
            return null;
        }

        return $this->findEntity('Oro\Bundle\MailChimpBundle\Entity\Member', $searchCondition, ['id']);
    }

    /**
     * @param MemberActivity $entity
     * @return bool
     */
    protected function isSkipped(MemberActivity $entity)
    {
        $searchCondition = null;
        if (in_array($entity->getAction(), $this->singleInstanceActivities, true)) {
            $searchCondition = [
                'campaign' => $entity->getCampaign(),
                'action' => $entity->getAction(),
                'member' => $entity->getMember()
            ];
        } else {
            $sinceMap = $this->context->getValue(MemberActivityConnector::SINCE_MAP_KEY);
            $campaignOriginId = $entity->getCampaign()->getOriginId();
            if ($sinceMap && array_key_exists($campaignOriginId, $sinceMap)) {
                $activitySince = $sinceMap[$campaignOriginId];
                if (array_key_exists($entity->getAction(), $activitySince)
                    && $entity->getActivityTime() <= $activitySince[$entity->getAction()]
                ) {
                    $searchCondition = [
                        'campaign' => $entity->getCampaign(),
                        'action' => $entity->getAction(),
                        'member' => $entity->getMember(),
                        'activityTime' => $entity->getActivityTime()
                    ];
                }
            }
        }

        if ($searchCondition) {
            return (bool)$this->findEntity(
                'Oro\Bundle\MailChimpBundle\Entity\MemberActivity',
                $searchCondition,
                ['id'],
                AbstractQuery::HYDRATE_SCALAR
            );
        }

        return false;
    }

    /**
     * Try to find entity by identity fields if at least one is specified
     *
     * @param string $entityName
     * @param array $identityValues
     * @param array $partialFields
     * @param null|int $hydration
     * @return null|object
     */
    protected function findEntity($entityName, array $identityValues, array $partialFields, $hydration = null)
    {
        foreach ($identityValues as $value) {
            if (null !== $value && '' !== $value) {
                return $this->findOneBy($entityName, $identityValues, $partialFields, $hydration);
            }
        }

        return null;
    }

    /**
     * @param string $entityName
     * @param array $criteria
     * @param array|null $partialFields
     * @param int|null $hydration
     * @return null|object
     */
    public function findOneBy(
        $entityName,
        array $criteria,
        ?array $partialFields = null,
        $hydration = null
    ) {
        $em = $this->strategyHelper->getEntityManager($entityName);

        $queryBuilder = $em->createQueryBuilder()->from($entityName, 'e');
        if ($partialFields) {
            array_walk($partialFields, [QueryBuilderUtil::class, 'checkIdentifier']);
            $queryBuilder->select(sprintf('partial e.{%s}', implode(',', $partialFields)));
        } else {
            $queryBuilder->select('e');
        }

        $where = $queryBuilder->expr()->andX();
        foreach ($criteria as $field => $value) {
            QueryBuilderUtil::checkIdentifier($field);
            $where->add(sprintf('e.%s = :%s', $field, $field));
        }

        $queryBuilder->where($where)
            ->setParameters($criteria)
            ->setMaxResults(1);

        return $queryBuilder->getQuery()->getOneOrNullResult($hydration);
    }

    /**
     * @param object $entity
     */
    protected function setOwner($entity)
    {
        if ($entity instanceof MemberActivity) {
            /** @var Channel $channel */
            $channel = $this->databaseHelper->getEntityReference($entity->getChannel());

            $this->ownerHelper->populateChannelOwner($entity, $channel);
        }
    }

    /**
     * @param DefaultOwnerHelper $ownerHelper
     */
    public function setOwnerHelper($ownerHelper)
    {
        $this->ownerHelper = $ownerHelper;
    }
}
