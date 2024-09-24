<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\ImportExportBundle\Converter\AbstractTableDataConverter;

/**
 * Data converter to export/import format for mailchimp member's extended merge variables data.
 */
class MemberExtendedMergeVarDataConverter extends AbstractTableDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'static_segment_id' => 'staticSegment:id',
            'member_id' => 'member:id',
            'merge_var_values' => 'mergeVarValues'
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \BadMethodCallException('Normalization is not implemented!');
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $itemData = parent::convertToImportFormat($importedRecord, $skipNullValues);
        if (empty($itemData['mergeVarValues'])) {
            $itemData['mergeVarValues'] = [];
        }

        return $itemData;
    }
}
