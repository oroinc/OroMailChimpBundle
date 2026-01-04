<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Oro\Bundle\MailChimpBundle\Util\CallbackFilterIteratorCompatible;

/**
 * Abstract mailchimp member activity iterator.
 */
abstract class AbstractMemberActivityIterator extends AbstractSubordinateIterator
{
    public const CAMPAIGN_KEY = 'campaign';

    /**
     * @var MailChimpClient
     */
    protected $client;

    public function __construct(\Iterator $campaignsIterator, MailChimpClient $client)
    {
        parent::__construct($campaignsIterator);
        $this->client = $client;
    }

    #[\Override]
    protected function createSubordinateIterator($campaign)
    {
        return new CallbackFilterIteratorCompatible(
            $this->createResultIterator($campaign),
            function (&$current) use ($campaign) {
                if ($current === null) {
                    return false;
                }

                $current[self::CAMPAIGN_KEY] = $campaign;
                return true;
            }
        );
    }

    /**
     * Create Campaign Aware Iterator
     *
     * @param Campaign $campaign
     * @return \Iterator
     */
    abstract protected function createResultIterator(Campaign $campaign);
}
