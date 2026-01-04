<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Integration connector for mailchimp member unsubscribe activity.
 */
class MemberUnsubscribeActivityConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    public const TYPE = 'member_activity_unsubscribe';
    public const JOB_IMPORT = 'mailchimp_member_activity_import_unsubscribe';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member_activity_unsubscribe.label';
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
        return $this->transport->getCampaignUnsubscribesReport($this->getChannel());
    }
}
