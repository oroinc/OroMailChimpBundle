<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Abstract data converter to export/import format for mailchimp member data.
 */
abstract class AbstractMemberDataConverter extends IntegrationAwareDataConverter
{
    /**
     * @inheridoc
     */
    protected function getHeaderConversionRules()
    {
        return [
            'id' => 'originId',
            'status' => 'status',
            'list_id' => 'subscribersList:originId',
            'email_address' => 'email',
            'member_rating' => 'memberRating',
            'timestamp_opt' => 'optedInAt',
            'ip_opt' => 'optedInIpAddress',
            'timestamp_signup' => 'confirmedAt',
            'ip_signup' => 'confirmedIpAddress',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'gmtoff' => 'gmtOffset',
            'dstoff' => 'dstOffset',
            'timezone' => 'timezone',
            'country_code' => 'cc',
            'region' => 'region',
            'last_changed' => 'lastChangedAt',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if ($this->context->hasOption('channel')) {
            $channel = $this->context->getOption('channel');
            $importedRecord['subscribersList:channel:id'] = $channel;
        }

        if (isset($importedRecord['location'])) {
            $importedRecord += $importedRecord['location'];
            unset($importedRecord['location']);
        }

        if (isset($importedRecord['merge_fields'])) {
            $importedRecord['mergeVarValues'] = $importedRecord['merge_fields'];
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isMergeVarValueColumn($name)
    {
        $headerConversionRules = $this->getHeaderConversionRules();

        return !isset($headerConversionRules[$name]) && $name !== 'subscribersList:channel:id';
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
