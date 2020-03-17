<?php

namespace Oro\Bundle\MailChimpBundle\Model\Segment;

use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Factory for segment's column definition list provider.
 */
class ColumnDefinitionListFactory
{
    /**
     * @param MarketingList $marketingList
     * @return ColumnDefinitionListInterface
     */
    public function create(MarketingList $marketingList)
    {
        $segment = $marketingList->getSegment();
        $columnDefinitionList = new ColumnDefinitionList($segment);
        return $columnDefinitionList;
    }
}
