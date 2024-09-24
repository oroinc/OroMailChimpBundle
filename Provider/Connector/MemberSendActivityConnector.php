<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Integration connector for mailchimp member send activity.
 */
class MemberSendActivityConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    const TYPE = 'member_activity_send';
    const JOB_IMPORT = 'mailchimp_member_activity_import_send';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member_activity_send.label';
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
        return $this->transport->getCampaignSentToReport($this->getChannel());
    }
}
