<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

/**
 * Integration connector for mailchimp static segment.
 */
class StaticSegmentConnector extends AbstractMailChimpConnector implements TwoWaySyncConnectorInterface
{
    public const TYPE = 'static_segment';
    public const JOB_IMPORT = 'mailchimp_static_segment_import';
    public const JOB_EXPORT = 'mailchimp_static_segment_export';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.staticSegment.label';
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
        return $this->transport->getSegmentsToSync($this->getChannel());
    }

    #[\Override]
    public function getExportJobName()
    {
        return self::JOB_EXPORT;
    }
}
