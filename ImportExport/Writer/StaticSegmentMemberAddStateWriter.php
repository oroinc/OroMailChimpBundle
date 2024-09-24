<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ImportExportBundle\Writer\CleanUpInterface;
use Oro\Bundle\ImportExportBundle\Writer\InsertFromSelectWriter;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegmentMember;

/**
 * Batch job's writer to remove synced mailchimp static segment's member.
 */
class StaticSegmentMemberAddStateWriter extends InsertFromSelectWriter implements CleanUpInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     * @return StaticSegmentMemberToRemoveWriter
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;

        return $this;
    }

    /**
     * @return \Doctrine\Persistence\ObjectManager|EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManager();
    }

    #[\Override]
    public function cleanUp(array $item)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->entityName, 'e')
            ->where($qb->expr()->eq('IDENTITY(e.staticSegment)', ':staticSegment'))
            ->andWhere($qb->expr()->neq('e.state', ':state'))
            ->setParameter('staticSegment', $item['static_segment_id'])
            ->setParameter('state', StaticSegmentMember::STATE_SYNCED);

        $qb->getQuery()->execute();
    }
}
