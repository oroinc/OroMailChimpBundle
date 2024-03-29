<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Integration connector for mailchimp member.
 */
class MemberConnector extends AbstractMailChimpConnector implements TwoWaySyncConnectorInterface
{
    const TYPE = 'member';
    const JOB_IMPORT = 'mailchimp_member_import';
    const JOB_EXPORT = 'mailchimp_member_export';

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member.label';
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
        return $this->transport->getMembersToSync($this->getChannel(), $this->getLastSyncDate());
    }

    /**
     * {@inheritdoc}
     */
    public function getExportJobName()
    {
        return self::JOB_EXPORT;
    }
}
