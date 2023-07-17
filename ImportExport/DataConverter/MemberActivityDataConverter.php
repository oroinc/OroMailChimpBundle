<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Data converter to export/import format for mailchimp member's activity data.
 */
class MemberActivityDataConverter extends IntegrationAwareDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'timestamp' => 'activityTime',
            'email_address' => 'email',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord['member:email'] = $importedRecord['email'] ?? $importedRecord['email_address'];
        $importedRecord['action'] = $importedRecord['activity']['action'] ?? $importedRecord['action'];
        $importedRecord['activityTime'] = $importedRecord['activity']['timestamp'] ?? $importedRecord['activityTime'];
        $importedRecord['ip'] = $importedRecord['activity']['ip'] ?? $importedRecord['ip'];

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
