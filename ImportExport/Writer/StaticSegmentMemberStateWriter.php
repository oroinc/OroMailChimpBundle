<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\StaticSegmentMemberToRemoveIterator;

/**
 * Batch job's mailchimp static segment's member state writer.
 */
class StaticSegmentMemberStateWriter extends AbstractNativeQueryWriter
{
    /**
     * @var NativeQueryExecutorHelper
     */
    protected $helper;

    public function __construct(NativeQueryExecutorHelper $helper)
    {
        $this->helper = $helper;
    }

    #[\Override]
    public function write(array $items)
    {
        foreach ($items as $item) {
            $qb = $this->getQueryBuilder($item);
            $selectQuery = $qb->getQuery();
            $staticSegmentId = $item[StaticSegmentMemberToRemoveIterator::STATIC_SEGMENT_ID];
            $state = $item[StaticSegmentMemberToRemoveIterator::STATE];

            list($params, $types) = $this->helper->processParameterMappings($selectQuery);

            $updateQuery = sprintf(
                "UPDATE %s
                    SET state = '%s'
                    WHERE
                      member_id IN (%s)
                      AND static_segment_id = %d",
                $this->helper->getTableName($this->entityName),
                $state,
                $selectQuery->getSQL(),
                $staticSegmentId
            );

            $this->helper->getManager($this->entityName)
                ->getConnection()
                ->executeStatement($updateQuery, $params, $types);
        }
    }
}
