<?php

namespace Oro\Bundle\MailChimpBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class ActivityUpdateRelatedFields implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroIntegrationTransportTable($schema);
        $this->updateOrocrmCmpgnTransportStngsTable($schema);

        $queries->addPostQuery(new UpdateActiveCampaignsQuery());
    }

    protected function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('orocrm_mailchimp_act_up_int', 'integer', ['notnull' => false]);
    }

    protected function updateOrocrmCmpgnTransportStngsTable(Schema $schema)
    {
        $table = $schema->getTable('orocrm_cmpgn_transport_stngs');
        $table->addColumn('mailchimp_receive_activities', 'boolean', ['notnull' => false]);
    }
}
