<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;
use Oro\Bundle\MailChimpBundle\Entity\Repository\MemberActivityRepository;

/**
 * Integration connector for mailchimp member activity.
 */
class MemberActivityConnector extends AbstractMailChimpConnector implements ConnectorInterface
{
    const TYPE = 'member_activity';
    const JOB_IMPORT = 'mailchimp_member_activity_import';
    const SINCE_MAP_KEY = 'since_map';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.mailchimp.connector.member_activity.label';
    }

    #[\Override]
    public function getImportEntityFQCN()
    {
        return $this->entityName;
    }

    #[\Override]
    public function getImportJobName()
    {
        return self::JOB_IMPORT;
    }

    #[\Override]
    public function getType()
    {
        return self::TYPE;
    }

    #[\Override]
    protected function getConnectorSource()
    {
        /** @var MemberActivityRepository $repository */
        $repository = $this->managerRegistry->getManagerForClass($this->entityName)
            ->getRepository($this->entityName);

        $latestActivityTimeMap = $repository->getLastSyncedActivitiesByCampaign(
            $this->getChannel(),
            [MemberActivity::ACTIVITY_CLICK, MemberActivity::ACTIVITY_OPEN]
        );
        foreach ($latestActivityTimeMap as $campaign => $sinceByAction) {
            if (!array_key_exists(MemberActivity::ACTIVITY_OPEN, $sinceByAction)) {
                $latestActivityTimeMap[$campaign][MemberActivity::ACTIVITY_OPEN] = null;
            }
            if (!array_key_exists(MemberActivity::ACTIVITY_CLICK, $sinceByAction)) {
                $latestActivityTimeMap[$campaign][MemberActivity::ACTIVITY_CLICK] = null;
            }
        }
        $context = $this->getContext();
        $context->setValue(self::SINCE_MAP_KEY, $latestActivityTimeMap);

        return $this->transport->getMemberActivitiesToSync($this->getChannel(), $latestActivityTimeMap);
    }
}
