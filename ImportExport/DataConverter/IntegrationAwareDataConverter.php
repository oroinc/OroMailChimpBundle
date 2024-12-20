<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Abstract data converter to export/import format for mailchimp integration aware data.
 */
abstract class IntegrationAwareDataConverter extends AbstractTableDataConverter implements ContextAwareInterface
{
    /**
     * @var ContextInterface
     */
    protected $context;

    #[\Override]
    public function setImportExportContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if (empty($importedRecord['channel_id'])) {
            $importedRecord['channel:id'] = $this->context->getOption('channel');
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
