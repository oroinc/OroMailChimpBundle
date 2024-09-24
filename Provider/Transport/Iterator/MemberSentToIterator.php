<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Campaign;

/**
 * Mailchimp members sent(subscribed) iterator.
 */
class MemberSentToIterator extends AbstractMemberActivityIterator
{
    /**
     * Creates iterator of sent and bounce activities for Campaign
     *
     * @param Campaign $campaign
     * @return \Iterator
     */
    #[\Override]
    protected function createResultIterator(Campaign $campaign)
    {
        return new CampaignSentToIterator($this->client, $campaign);
    }
}
