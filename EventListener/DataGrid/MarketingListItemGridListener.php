<?php

namespace Oro\Bundle\MailChimpBundle\EventListener\DataGrid;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
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
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;
use Oro\Bundle\MailChimpBundle\Model\FieldHelper;
use Oro\Bundle\MailChimpBundle\Placeholder\MarketingListPlaceholderFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Model\MarketingListHelper;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;

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
     * @var ContactInformationFieldsProvider
     */
    private $contactInformationFieldsProvider;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var MarketingListPlaceholderFilter
     */
    private $marketingListPlaceholderFilter;

    public function __construct(
        MarketingListHelper $marketingListHelper,
        ContactInformationFieldsProvider $contactInformationFieldsProvider,
        FieldHelper $fieldHelper,
        MarketingListPlaceholderFilter $marketingListPlaceholderFilter
    ) {
        $this->marketingListHelper = $marketingListHelper;
        $this->contactInformationFieldsProvider = $contactInformationFieldsProvider;
        $this->fieldHelper = $fieldHelper;
        $this->marketingListPlaceholderFilter = $marketingListPlaceholderFilter;
    }

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
            $this->joinSubscriberState($marketingList, $datasource->getQueryBuilder());
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
     *
     * @return bool
     */
    private function isApplicableOnMarketingList(MarketingList $marketingList)
    {
        return $this->marketingListPlaceholderFilter->isApplicableOnMarketingList($marketingList);
    }

    /**
     * Join real subscriber status
     */
    private function joinSubscriberState(MarketingList $marketingList, QueryBuilder $queryBuilder)
    {
        $contactInformationFields = $this->contactInformationFieldsProvider->getMarketingListTypedFields(
            $marketingList,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );

        if (!$contactInformationField = reset($contactInformationFields)) {
            throw new \RuntimeException('Contact information is not provided');
        }

        $expr = $queryBuilder->expr();

        $contactInformationFieldExpr = $this->fieldHelper
            ->getFieldExpr($marketingList->getEntity(), $queryBuilder, $contactInformationField);
        $queryBuilder->addSelect($expr->lower($contactInformationFieldExpr) . ' AS entityEmail');

        $joinContactsExpr = $expr->andX()
            ->add(
                $expr->eq(
                    $expr->lower($contactInformationFieldExpr),
                    'mc_mlist_email.email'
                )
            );
        $joinContactsExpr->add('mc_mlist_email.marketingList =:marketingList');

        $queryBuilder->leftJoin(
            MarketingListEmail::class,
            'mc_mlist_email',
            Join::WITH,
            $joinContactsExpr
        )
            ->setParameter('marketingList', $marketingList)
            ->addSelect('mc_mlist_email.state as mcEmailState');
    }

    private function rewriteActionConfiguration(DatagridInterface $datagrid)
    {
        $config = $datagrid->getConfig();
        // original closure results for permissions are used
        $actionConfiguration = $config->offsetGetOr(ActionExtension::ACTION_CONFIGURATION_KEY);
        $callable = function (ResultRecordInterface $record) use ($config, $actionConfiguration) {
            $permissions = $actionConfiguration && is_callable($actionConfiguration) ?
                $actionConfiguration($record, $config->offsetGetOr(ActionExtension::ACTION_KEY, [])) : null;

            $permissions = is_array($permissions) ? $permissions : [];
            $permissions['subscribe'] = (isset($permissions['subscribe']) ? $permissions['subscribe'] : false) &&
                $record->getValue('mcEmailState') !== 'unsubscribe';

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
