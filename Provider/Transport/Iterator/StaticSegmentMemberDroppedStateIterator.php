<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\MailChimpBundle\Entity\MemberExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegmentMember;

/**
 * Mailchimp static segment's member dropped iterator.
 */
class StaticSegmentMemberDroppedStateIterator extends AbstractSubordinateIterator
{
    /**
     * @internal
     */
    const STATIC_SEGMENT_ID = 'static_segment_id';

    /**
     * @internal
     */
    const STATE = 'state';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $memberEntity;

    /**
     * @var string
     */
    protected $memberExtendedMergeVarEntity;

    public function __construct(\Iterator $mainIterator = null)
    {
        if ($mainIterator) {
            $this->setMainIterator($mainIterator);
        }
    }

    public function setMainIterator(\Iterator $mainIterator)
    {
        $this->mainIterator = $mainIterator;
    }

    /**
     * @param ManagerRegistry $registry
     *
     * @return $this
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param string $memberEntity
     *
     * @return $this
     */
    public function setMemberEntity($memberEntity)
    {
        $this->memberEntity = $memberEntity;

        return $this;
    }

    /**
     * @param string $memberExtendedMergeVarEntity
     *
     * @return $this
     */
    public function setMemberExtendedMergeVarEntity($memberExtendedMergeVarEntity)
    {
        $this->memberExtendedMergeVarEntity = $memberExtendedMergeVarEntity;

        return $this;
    }

    /**
     * @param StaticSegment $staticSegment
     *
     * @return \Iterator
     */
    protected function createSubordinateIterator($staticSegment)
    {
        if (!$this->memberEntity) {
            throw new \InvalidArgumentException('Member entity class name must be provided');
        }

        if (!$this->memberExtendedMergeVarEntity) {
            throw new \InvalidArgumentException('Marketing List Email entity class name must be provided');
        }

        /** @var EntityManager $repository */
        $repository = $this->registry->getManager();
        $qb = $repository->createQueryBuilder();

        $qb
            ->select(['mmb.id member_id'])
            ->from($this->memberEntity, 'mmb')
            ->innerJoin(
                $this->memberExtendedMergeVarEntity,
                'mmbMergeVar',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('mmbMergeVar.member', 'mmb'),
                    $qb->expr()->eq('mmbMergeVar.staticSegment', $staticSegment->getId())
                )
            )
            ->where($qb->expr()->andX(
                $qb->expr()->neq('mmb.status', ':droppedState'),
                $qb->expr()->eq('mmbMergeVar.state', ':droppedState')
            ))
            ->groupBy('mmb.id')
            ->setParameter('droppedState', MemberExtendedMergeVar::STATE_DROPPED);

        return new \ArrayIterator(
            [
                [
                    AbstractNativeQueryWriter::QUERY_BUILDER => $qb,
                    self::STATIC_SEGMENT_ID => $staticSegment->getId(),
                    self::STATE => StaticSegmentMember::STATE_TO_DROP,
                ]
            ]
        );
    }
}
