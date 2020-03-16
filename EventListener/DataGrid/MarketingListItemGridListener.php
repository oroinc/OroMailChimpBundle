<?php

namespace Oro\Bundle\MailChimpBundle\EventListener\DataGrid;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\EventListener\MixinListener;
use Oro\Bundle\DataGridBundle\Extension\Action\ActionExtension;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Configuration;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\MailChimpBundle\Placeholder\MarketingListPlaceholderFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Model\MarketingListHelper;

/**
 * Hides subscribe action for unsubscribed marketing list items for a list connected to mailchimp.
 */
class MarketingListItemGridListener
{
    /**
     * @var MarketingListHelper
     */
    private $marketingListHelper;

    /**
     * @var MarketingListPlaceholderFilter
     */
    private $marketingListPlaceholderFilter;

    /**
     * @param MarketingListHelper $marketingListHelper
     * @param MarketingListPlaceholderFilter $marketingListPlaceholderFilter
     */
    public function __construct(
        MarketingListHelper $marketingListHelper,
        MarketingListPlaceholderFilter $marketingListPlaceholderFilter
    ) {
        $this->marketingListHelper = $marketingListHelper;
        $this->marketingListPlaceholderFilter = $marketingListPlaceholderFilter;
    }

    /**
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        if (!$this->isApplicable($datagrid->getName(), $datagrid->getParameters())) {
            return;
        }

        /** @var OrmDatasource $datasource */
        $datasource = $datagrid->getDatasource();
        $mixin = $datagrid->getParameters()->get(MixinListener::GRID_MIXIN);
        $marketingList = $this->getMarketingListFromDatasource($datasource);

        if ($mixin === 'oro-marketing-list-items-mixin' &&
            $marketingList instanceof MarketingList &&
            $this->isApplicableOnMarketingList($marketingList)
        ) {
            $this->rewriteActionConfiguration($datagrid);
        }
    }

    /**
     * @param DatasourceInterface $datasource
     *
     * @return MarketingList|null
     */
    private function getMarketingListFromDatasource(DatasourceInterface $datasource)
    {
        $marketingList = null;
        if ($datasource instanceof OrmDatasource) {
            $mlParameter = $datasource->getQueryBuilder()->getParameter('marketingListEntity');
            $marketingList = $mlParameter ? $mlParameter->getValue() : null;
        }

        return $marketingList;
    }

    /**
     * Accept oro_marketing_list_items_grid_* grids only in case when they has mixin to apply.
     *
     * @param string $gridName
     * @param ParameterBag $parameters
     *
     * @return bool
     */
    private function isApplicable($gridName, $parameters)
    {
        if (!$parameters->get(MixinListener::GRID_MIXIN, false)) {
            return false;
        }

        return (bool)$this->marketingListHelper->getMarketingListIdByGridName($gridName);
    }

    /**
     * @param MarketingList $marketingList
     * @return
     */
    private function isApplicableOnMarketingList(MarketingList $marketingList)
    {
        return $this->marketingListPlaceholderFilter->isApplicableOnMarketingList($marketingList);
    }

    /**
     * @param DatagridInterface $datagrid
     */
    private function rewriteActionConfiguration(DatagridInterface $datagrid)
    {
        $config = $datagrid->getConfig();
        $actionConfiguration = $config->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY);
        $callable = function (ResultRecordInterface $record) use ($config, $actionConfiguration) {
            $permissions = $actionConfiguration && is_callable($actionConfiguration) ?
                $actionConfiguration($record, $config->offsetGetOr(ActionExtension::ACTION_KEY, [])) : null;

            $permissions = is_array($permissions) ? $permissions : [];
            $permissions['subscribe'] = false;

            return $permissions;
        };

        $propertyConfig = [
            'type' => 'callback',
            'callable' => $callable,
            PropertyInterface::FRONTEND_TYPE_KEY => PropertyInterface::TYPE_ROW_ARRAY
        ];

        $config->offsetAddToArrayByPath(
            sprintf(
                '[%s][%s]',
                Configuration::PROPERTIES_KEY,
                ActionExtension::METADATA_ACTION_CONFIGURATION_KEY
            ),
            $propertyConfig
        );
    }
}
