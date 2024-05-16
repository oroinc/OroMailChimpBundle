<?php

namespace Oro\Bundle\MailChimpBundle\Model\StaticSegment;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegmentMember;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;

/**
 * Mailchimp static segment members state manager.
 */
class StaticSegmentsMemberStateManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $staticSegmentMember;

    /**
     * @var string
     */
    protected $mailChimpMemberClassName;

    /**
     * @var string
     */
    protected $extMergeVarClassName;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        $staticSegmentMember,
        $mailChimpMemberClassName,
        $extMergeVarClassName
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->staticSegmentMember = $staticSegmentMember;
        $this->mailChimpMemberClassName = $mailChimpMemberClassName;
        $this->extMergeVarClassName = $extMergeVarClassName;
    }

    public function handleMembers(StaticSegment $staticSegment)
    {
        $staticSegmentRep = $this->doctrineHelper->getEntityRepository($this->staticSegmentMember);

        $qb = $staticSegmentRep->createQueryBuilder('smmb');

        $deletedMembers = $qb
            ->select('IDENTITY(smmb.member) AS memberId')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('smmb.staticSegment', $staticSegment->getId()),
                    $qb->expr()->eq('smmb.state', ':stateUnsDel')
                )
            )
            ->setParameter('stateUnsDel', StaticSegmentMember::STATE_UNSUBSCRIBE_DELETE)
            ->getQuery()
            ->getArrayResult();

        $this->handleDroppedMembers($staticSegment);

        if ($deletedMembers) {
            $deletedMembersIds = array_map('current', $deletedMembers);
            $this->deleteMailChimpMembers($deletedMembersIds, $staticSegment->getSubscribersList());
            $this->deleteMailChimpMembersExtendedVars($deletedMembersIds, $staticSegment->getId());
        }
    }

    protected function handleDroppedMembers(StaticSegment $staticSegment)
    {
        $qb = $this->doctrineHelper
            ->getEntityRepository($this->staticSegmentMember)
            ->createQueryBuilder('smmb');

        $qb
            ->delete($this->staticSegmentMember, 'smmb')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('smmb.staticSegment', $staticSegment->getId()),
                    $qb->expr()->in('smmb.state', ':states')
                )
            )
            ->setParameter('states', [StaticSegmentMember::STATE_DROP, StaticSegmentMember::STATE_UNSUBSCRIBE_DELETE])
            ->getQuery()
            ->execute();
    }

    protected function deleteMailChimpMembers(array $deletedMembersIds, SubscribersList $subscribersList)
    {
        $qb = $this->doctrineHelper
            ->getEntityRepository($this->mailChimpMemberClassName)
            ->createQueryBuilder('mmb');

        $qb
            ->delete($this->mailChimpMemberClassName, 'mmb')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('mmb.id', ':deletedMembersIds'),
                    $qb->expr()->eq('mmb.subscribersList', $subscribersList->getId())
                )
            )
            ->setParameter('deletedMembersIds', $deletedMembersIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $deletedMembersIds
     * @param integer $staticSegmentId
     */
    protected function deleteMailChimpMembersExtendedVars(array $deletedMembersIds, $staticSegmentId)
    {
        $qb = $this->doctrineHelper
            ->getEntityRepository($this->extMergeVarClassName)
            ->createQueryBuilder('evmmb');

        $qb
            ->delete($this->extMergeVarClassName, 'evmmb')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->in('evmmb.member', ':deletedMembersIds'),
                    $qb->expr()->eq('evmmb.staticSegment', $staticSegmentId)
                )
            )
            ->setParameter('deletedMembersIds', $deletedMembersIds)
            ->getQuery()
            ->execute();
    }
}
