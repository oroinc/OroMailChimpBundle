<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

/**
 * Integration connector for mailchimp template.
 */
class TemplateConnector extends AbstractMailChimpConnector
{
    const TYPE = 'template';
    const JOB_IMPORT = 'mailchimp_template_import';

    #[\Override]
    protected function getConnectorSource()
    {
        return $this->transport->getTemplates();
    }

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.template.label';
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
}
