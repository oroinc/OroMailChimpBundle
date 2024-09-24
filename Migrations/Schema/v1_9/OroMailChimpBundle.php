<?php

namespace Oro\Bundle\MailChimpBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\MigrationConstraintTrait;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroMailChimpBundle implements Migration, DatabasePlatformAwareInterface
{
    use DatabasePlatformAwareTrait;
    use MigrationConstraintTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->changeOriginIdOnMailchimpMember($schema);
        $queries->addPostQuery(new UpdateOriginIdQuery($this->platform));
    }

    private function changeOriginIdOnMailchimpMember(Schema $schema)
    {
        $mailChimpMemberTable = $schema->getTable('orocrm_mailchimp_member');

        $mailChimpMemberTable
            ->addColumn('leid', Types::BIGINT)
            ->setNotnull(false);

        $mailChimpMemberTable
            ->getColumn('origin_id')
            ->setType(Type::getType(Types::STRING))
            ->setNotnull(false)
            ->setLength(32);
    }
}
