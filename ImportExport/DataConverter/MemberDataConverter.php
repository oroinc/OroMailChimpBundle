<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Data converter to export/import format for mailchimp member data.
 */
class MemberDataConverter extends AbstractMemberDataConverter
{
    public const IMPORT_DATA = '_is_import_data_';

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord = parent::convertToImportFormat($importedRecord, $skipNullValues);
        // Add import mark to trigger simplified serializer to use
        $importedRecord[self::IMPORT_DATA] = true;

        return $importedRecord;
    }
}
