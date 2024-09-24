<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Exception;

/**
 * Mailchimp campaign unsubscribe report iterator.
 */
class CampaignUnsubscribesIterator extends AbstractCampaignAwareIterator
{
    /**
     * @return array
     * @throws Exception
     */
    #[\Override]
    protected function getResult()
    {
        $unsubscribes = $this->client->getCampaignUnsubscribesReport($this->getArguments());

        return [
            'data' => $unsubscribes['unsubscribes'],
            'total' => $unsubscribes['total_items'],
        ];
    }
}
