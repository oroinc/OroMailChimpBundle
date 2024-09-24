<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Campaign;

/**
 * Mailchimp members unsubscribe reports iterator.
 */
class MemberUnsubscribesIterator extends AbstractMemberActivityIterator
{
    /**
     * Creates iterator of unsibscribed members for Campaign
     *
     * @param Campaign $campaign
     * @return \Iterator
     */
    #[\Override]
    protected function createResultIterator(Campaign $campaign)
    {
        return new CampaignUnsubscribesIterator($this->client, $campaign);
    }
}
