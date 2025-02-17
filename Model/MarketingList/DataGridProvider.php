<?php

namespace Oro\Bundle\MailChimpBundle\Model\MarketingList;

use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListProvider;

/**
 * Marketing list data grid provider
 */
class DataGridProvider extends MarketingListProvider implements DataGridProviderInterface
{
    #[\Override]
    public function getDataGridConfiguration(MarketingList $marketingList)
    {
        if ($marketingList->isManual()) {
            $mixin = MarketingListProvider::MANUAL_RESULT_ENTITIES_MIXIN;
        } else {
            $mixin = MarketingListProvider::RESULT_ENTITIES_MIXIN;
        }

        $dataGrid = $this->getMarketingListDataGrid($marketingList, $mixin);
        return $dataGrid->getConfig();
    }
}
