<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;

/**
 * Data converter to export/import format for mailchimp member unsubscribe activity data.
 */
class MemberUnsubscribeActivityDataConverter extends AbstractMemberActivityDataConverter
{
    /**
     * {@inheritdoc}
     */
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord['action'] = MemberActivity::ACTIVITY_UNSUB;

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
