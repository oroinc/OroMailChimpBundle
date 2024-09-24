<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Abstract data converter to export/import format for mailchimp member activity data.
 */
abstract class AbstractMemberActivityDataConverter extends IntegrationAwareDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'campaign' => 'campaign'
        ];
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord['member:originId'] = $importedRecord['email_id'];
        $importedRecord['email'] = $importedRecord['email_address'];

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
