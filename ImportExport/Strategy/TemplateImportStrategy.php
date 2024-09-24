<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Strategy;

use Oro\Bundle\MailChimpBundle\Entity\Template;

/**
 * Mailchimp template import strategy.
 */
class TemplateImportStrategy extends AbstractImportStrategy
{
    /**
     * @param Template $entity
     * @return Template
     */
    #[\Override]
    protected function beforeProcessEntity($entity)
    {
        if ($this->logger) {
            $this->logger->info('Syncing MailChimp Template [origin_id=' . $entity->getOriginId() . ']');
        }

        return parent::beforeProcessEntity($entity);
    }
}
