<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Data converter to export/import format for mailchimp extended merge variables data.
 */
class ExtendedMergeVarDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'name' => 'name',
            'label' => 'label',
            'static_segment_id' => 'staticSegment:id',
            'state' => 'state'
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \BadMethodCallException('Normalization is not implemented!');
    }
}
