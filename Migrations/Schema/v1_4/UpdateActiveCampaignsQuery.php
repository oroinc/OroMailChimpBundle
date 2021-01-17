<?php

namespace Oro\Bundle\MailChimpBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateActiveCampaignsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->updateSettings($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateSettings($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateSettings(LoggerInterface $logger, $dryRun = false)
    {
        $update = 'UPDATE orocrm_cmpgn_transport_stngs
          SET mailchimp_receive_activities = :receiveActivities
          WHERE type = :type';
        $params = ['receiveActivities' => true, 'type' => 'mailchimptransportsettings'];
        $types = ['receiveActivities' => Types::BOOLEAN, 'type' => Types::STRING];
        $this->logQuery($logger, $update, $params, $types);

        if (!$dryRun) {
            $this->connection->executeStatement($update, $params, $types);
        }
    }
}
