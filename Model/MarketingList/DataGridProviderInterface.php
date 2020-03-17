<?php

namespace Oro\Bundle\MailChimpBundle\Model\MarketingList;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Marketing list data grid provider interface.
 */
interface DataGridProviderInterface
{
    /**
     * @param MarketingList $marketingList
     * @return DatagridConfiguration
     */
    public function getDataGridConfiguration(MarketingList $marketingList);
}
