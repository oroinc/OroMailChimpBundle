<?php

namespace Oro\Bundle\MailChimpBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManagerAwareTrait;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * The origin_id field cannot be unique, as there are situations when email addresses are written in different case.
 */
class UpdateEmailFieldImportExportConfig implements Migration, ExtendOptionsManagerAwareInterface
{
    use ExtendOptionsManagerAwareTrait;

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateMemberConfiguration($queries);
    }

    private function updateMemberConfiguration(QueryBag $queries): void
    {
        $this->extendOptionsManager->mergeColumnOptions(
            'orocrm_mailchimp_member',
            'email',
            ['importexport' => ['identity' => true]]
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(Member::class, 'email', 'importexport', 'identity', true)
        );

        $this->extendOptionsManager->mergeColumnOptions(
            'orocrm_mailchimp_member',
            'origin_id',
            ['importexport' => ['identity' => false]]
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(Member::class, 'email', 'importexport', 'identity', false)
        );
    }
}
