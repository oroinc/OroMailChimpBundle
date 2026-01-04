<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;

/**
 * Integration connector for mailchimp campaign.
 */
class CampaignConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    public const TYPE = 'campaign';
    public const JOB_IMPORT = 'mailchimp_campaign_import';
    public const JOB_EXPORT = 'mailchimp_campaign_export';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.campaign.label';
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
        return $this->transport->getCampaigns($this->getChannel(), Campaign::STATUS_SENT, true);
    }
}
