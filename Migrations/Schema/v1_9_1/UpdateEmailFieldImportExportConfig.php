<?php

namespace Oro\Bundle\MailChimpBundle\Migrations\Schema\v1_9_1;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * The origin_id field cannot be unique, as there are situations when email addresses are written in different case.
 */
class UpdateEmailFieldImportExportConfig implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateMemberConfiguration($queries);
    }

    private function updateMemberConfiguration(QueryBag $queries): void
    {
        $extendOptionsManager = $this->container->get('oro_entity_extend.migration.options_manager');

        $extendOptionsManager->mergeColumnOptions(
            'orocrm_mailchimp_member',
            'email',
            ['importexport' => ['identity' => true]]
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(Member::class, 'email', 'importexport', 'identity', true)
        );

        $extendOptionsManager->mergeColumnOptions(
            'orocrm_mailchimp_member',
            'origin_id',
            ['importexport' => ['identity' => false]]
        );
        $queries->addPostQuery(
            new UpdateEntityConfigFieldValueQuery(Member::class, 'email', 'importexport', 'identity', false)
        );
    }
}
