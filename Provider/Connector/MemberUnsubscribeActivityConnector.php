<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;

/**
 * Integration connector for mailchimp member unsubscribe activity.
 */
class MemberUnsubscribeActivityConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    const TYPE = 'member_activity_unsubscribe';
    const JOB_IMPORT = 'mailchimp_member_activity_import_unsubscribe';

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member_activity_unsubscribe.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        return $this->transport->getCampaignUnsubscribesReport($this->getChannel());
    }
}
