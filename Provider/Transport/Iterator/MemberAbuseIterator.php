<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

/**
 * Mailchimp member abuse report iterator.
 */
class MemberAbuseIterator extends AbstractMemberActivityIterator
{
    /**
     * @var string
     */
    protected $since;

    /**
     * @param \Iterator $campaignsIterator
     * @param string $since
     * @param MailChimpClient $client
     */
    public function __construct(\Iterator $campaignsIterator, $since, MailChimpClient $client)
    {
        parent::__construct($campaignsIterator, $client);

        $this->since = $since;
    }

    /**
     * Creates iterator of sent and bounce activities for Campaign
     *
     * @param Campaign $campaign
     * @return \Iterator
     */
    #[\Override]
    protected function createResultIterator(Campaign $campaign)
    {
        return new CampaignAbuseIterator($this->client, $campaign, $this->since);
    }
}
