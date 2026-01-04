<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Integration connector for mailchimp member.
 */
class MemberConnector extends AbstractMailChimpConnector implements TwoWaySyncConnectorInterface
{
    public const TYPE = 'member';
    public const JOB_IMPORT = 'mailchimp_member_import';
    public const JOB_EXPORT = 'mailchimp_member_export';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member.label';
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
        return $this->transport->getMembersToSync($this->getChannel(), $this->getLastSyncDate());
    }

    #[\Override]
    public function getExportJobName()
    {
        return self::JOB_EXPORT;
    }
}
