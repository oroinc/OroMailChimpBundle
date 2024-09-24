<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;

/**
 * Data converter to export/import format for mailchimp member's abuse activity data.
 */
class MemberAbuseActivityDataConverter extends AbstractMemberActivityDataConverter
{
    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $importedRecord['action'] = MemberActivity::ACTIVITY_ABUSE;

        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }
}
