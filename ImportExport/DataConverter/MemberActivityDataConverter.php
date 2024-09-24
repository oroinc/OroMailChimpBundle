<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Data converter to export/import format for mailchimp member's activity data.
 */
class MemberActivityDataConverter extends IntegrationAwareDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'timestamp' => 'activityTime',
            'email_address' => 'email',
        ];
    }

    /**
     * Please note that if there is no activity data, the activity entity will be ignored and not saved.
     *
     */
    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord['member:email'] = $importedRecord['email_address'];

        if (isset($importedRecord['activity']['action'])) {
            $importedRecord['action'] = $importedRecord['activity']['action'];
        }

        if (isset($importedRecord['activity']['timestamp'])) {
            $importedRecord['activityTime'] = $importedRecord['activity']['timestamp'];
        }

        if (isset($importedRecord['activity']['ip'])) {
            $importedRecord['ip'] = $importedRecord['activity']['ip'];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
