<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

/**
 * Mailchimp report campaign iterator.
 */
class ReportsCampaignEmailActivitySubordinateIterator extends AbstractMemberActivityIterator
{
    private array $sinceMap;

    public function __construct(\Iterator $campaignsIterator, MailChimpClient $client, array $sinceMap)
    {
        $this->sinceMap = $sinceMap;

        parent::__construct($campaignsIterator, $client);
    }

    #[\Override]
    protected function createResultIterator(Campaign $campaign): ReportsCampaignEmailActivityIterator
    {
        $reportsCampaignEmailActivityIterator = new ReportsCampaignEmailActivityIterator($this->client);
        $reportsCampaignEmailActivityIterator->setCampaignId($campaign->getOriginId());
        if (!empty($this->sinceMap[$campaign->getOriginId()]['since'])) {
            $parameters['since'] = $this->sinceMap[$campaign->getOriginId()]['since'];
            $reportsCampaignEmailActivityIterator->setOptions($parameters);
        }

        return $reportsCampaignEmailActivityIterator;
    }
}
