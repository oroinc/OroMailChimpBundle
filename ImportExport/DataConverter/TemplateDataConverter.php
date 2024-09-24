<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Data converter to export/import format for mailchimp template data.
 */
class TemplateDataConverter extends IntegrationAwareDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            'origin_id' => 'originId',
            'preview_image' => 'previewImage',
            'date_created' => 'createdAt'
        ];
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
