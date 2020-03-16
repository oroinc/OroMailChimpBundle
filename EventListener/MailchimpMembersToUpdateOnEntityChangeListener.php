<?php

namespace Oro\Bundle\MailChimpBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListAllowedClassesProvider;

/**
 * Doctrine onFlush event listener to detect mailchimp subscribers to be updated based on changes made to their
 * related source items like contacts, customer user, etc.
 */
class MailchimpMembersToUpdateOnEntityChangeListener
{
    private const UPDATE_MEMBERS_STATUSES_BATCH_SIZE = 50;

    /**
     * @var MarketingList[]
     */
    private $marketingListsBySource = [];

    /**
     * @var array
     */
    private $entityTypedFields = [];

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var MarketingListAllowedClassesProvider
     */
    private $marketingListAllowedClassesProvider;

    /**
     * @var ContactInformationFieldsProvider
     */
    protected $contactInformationFieldsProvider;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param MarketingListAllowedClassesProvider $marketingListAllowedClassesProvider
     * @param ContactInformationFieldsProvider $contactInformationFieldsProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        MarketingListAllowedClassesProvider $marketingListAllowedClassesProvider,
        ContactInformationFieldsProvider $contactInformationFieldsProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->marketingListAllowedClassesProvider = $marketingListAllowedClassesProvider;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();

        $entitiesToUpdates = $uow->getScheduledEntityUpdates();
        if (!count($entitiesToUpdates)) {
            return;
        }

        $allowedEntities = $this->getAllowedEntities();

        $entitiesBatchCounter = 0;
        $emails = [];
        foreach ($entitiesToUpdates as $entity) {
            $originalClass = $this->getOriginalClassIfAllowed($entity, $allowedEntities);
            if (!$originalClass) {
                continue;
            }

            if ($entity instanceof EmailHolderInterface) {
                $emailsToAdd = array((string)$entity->getEmail());
            } else {
                $typedFields = $this->getEntityEmailFields($originalClass);

                try {
                    $emailsToAdd =
                        $this->contactInformationFieldsProvider->getTypedFieldsValues($typedFields, $entity);
                } catch (\Exception $e) {
                    $emailsToAdd = [];
                }
            }

            $emails = array_merge($emails, $emailsToAdd);


            $entitiesBatchCounter++;
            if ($entitiesBatchCounter >= self::UPDATE_MEMBERS_STATUSES_BATCH_SIZE) {
                $this->updateMembersSyncStatus(array_unique($emails));
                $entitiesBatchCounter = 0;
                $emails = [];
            }
        }

        if (count($emails)) {
            $this->updateMembersSyncStatus(array_unique($emails));
        }
    }

    /**
     * @param string $entityClass
     * @return array
     */
    private function getEntityEmailFields(string $entityClass)
    {
        if (!array_key_exists($entityClass, $this->entityTypedFields)) {
            $this->entityTypedFields[$entityClass] = $this->contactInformationFieldsProvider
                ->getEntityTypedFields(
                    $entityClass,
                    ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
                );
        }

        return $this->entityTypedFields[$entityClass];
    }

    /**
     * @param object $entity
     * @param string[] $allowedClasses
     * @return bool|string
     */
    private function getOriginalClassIfAllowed($entity, array $allowedClasses)
    {
        foreach ($allowedClasses as $allowedClass) {
            if (is_a($entity, $allowedClass)) {
                return $allowedClass;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    private function getAllowedEntities()
    {
        return $this->marketingListAllowedClassesProvider->getList();
    }

    /**
     * Sets mailchimp members to 'update' status by given emails
     *
     * @param array $mails
     * @return mixed
     */
    private function updateMembersSyncStatus(array $emails)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->doctrineHelper
            ->getEntityRepository(Member::class)
            ->createQueryBuilder('mc_member');

        return $qb->update()->set('mc_member.status', ':status')
            ->where($qb->expr()->in('mc_member.email', ':emails'))
            ->andWhere($qb->expr()->in('mc_member.status', ':filterStatus'))
            ->setParameter('status', Member::STATUS_UPDATE)
            ->setParameter('filterStatus', [Member::STATUS_SUBSCRIBED])
            ->setParameter('emails', $emails)
            ->getQuery()->execute();
    }
}
