<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;

/**
 * Data converter to export/import format for mailchimp member send activity data.
 */
class MemberSendActivityDataConverter extends AbstractMemberActivityDataConverter
{
    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        if ($importedRecord['status'] === MemberActivity::ACTIVITY_SENT) {
            $importedRecord['action'] = MemberActivity::ACTIVITY_SENT;
        } else {
            $importedRecord['action'] = MemberActivity::ACTIVITY_BOUNCE;
        }

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
