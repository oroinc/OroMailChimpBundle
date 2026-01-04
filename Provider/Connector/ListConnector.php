<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Integration connector for mailchimp subscribers list.
 */
class ListConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    public const TYPE = 'list';
    public const JOB_IMPORT = 'mailchimp_list_import';
    public const JOB_EXPORT = 'mailchimp_list_export';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.list.label';
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }

    #[\Override]
    protected function getConnectorSource()
    {
        return $this->transport->getLists();
    }
}
