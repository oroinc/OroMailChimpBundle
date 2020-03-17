<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Writer\CleanUpInterface;
use Oro\Bundle\ImportExportBundle\Writer\InsertFromSelectWriter;

/**
 * Batch job's mailchimp static segment's member to remove writer.
 */
class StaticSegmentMemberToRemoveWriter extends InsertFromSelectWriter implements CleanUpInterface
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
     * @return \Doctrine\Common\Persistence\ObjectManager|EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function cleanUp(array $item)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete($this->entityName, 'e')
            ->where($qb->expr()->eq('IDENTITY(e.staticSegment)', ':staticSegment'))
            ->setParameter('staticSegment', $item['static_segment_id']);

        $qb->getQuery()->execute();
    }
}
