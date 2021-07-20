<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegmentMember;

/**
 * Mailchimp static segment's member remove state iterator.
 */
class StaticSegmentMemberRemoveStateIterator extends AbstractSubordinateIterator
{
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
    protected $marketingListEmailEntity;

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
     * @return StaticSegmentMemberRemoveStateIterator
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @param string $memberEntity
     * @return StaticSegmentMemberRemoveStateIterator
     */
    public function setMemberEntity($memberEntity)
    {
        $this->memberEntity = $memberEntity;

        return $this;
    }

    /**
     * @param string $marketingListEmailEntity
     * @return StaticSegmentMemberRemoveStateIterator
     */
    public function setMarketingListEmailEntity($marketingListEmailEntity)
    {
        $this->marketingListEmailEntity = $marketingListEmailEntity;

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
        if (!$this->marketingListEmailEntity) {
            throw new \InvalidArgumentException('Marketing List Email entity class name must be provided');
        }

        /** @var EntityManager $repository */
        $repository = $this->registry->getManager();
        $qb = $repository->createQueryBuilder();

        $qb
            ->select(
                [
                    'mmb.id member_id',
                    $staticSegment->getId() . ' static_segment_id',
                    sprintf(
                        'COALESCE(MAX(mlEmail.state), %s) state',
                        $qb->expr()->literal(StaticSegmentMember::STATE_REMOVE)
                    )
                ]
            )
            ->from($this->memberEntity, 'mmb')
            ->innerJoin(
                'mmb.segmentMembers',
                'segmentMembers',
                Join::WITH,
                $qb->expr()->eq('segmentMembers.staticSegment', $staticSegment->getId())
            )
            ->leftJoin(
                $this->marketingListEmailEntity,
                'mlEmail',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('mmb.email', 'mlEmail.email'),
                    $qb->expr()->eq('mlEmail.marketingList', ':marketingList')
                )
            )
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->neq('mlEmail.state', ':state'),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('IDENTITY(mlEmail.marketingList)'),
                        $qb->expr()->isNotNull('mmb.originId'),
                        $qb->expr()->eq('mmb.subscribersList', ':subscribersList')
                    )
                )
            )
            ->groupBy('mmb.id')
            ->setParameter('state', MarketingListEmail::STATE_IN_LIST)
            ->setParameter('marketingList', $staticSegment->getMarketingList())
            ->setParameter('subscribersList', $staticSegment->getSubscribersList());

        return new \ArrayIterator(
            [
                [
                    AbstractNativeQueryWriter::QUERY_BUILDER => $qb,
                    'static_segment_id' => $staticSegment->getId()
                ]
            ]
        );
    }
}
