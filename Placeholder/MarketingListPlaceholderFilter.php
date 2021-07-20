<?php

namespace Oro\Bundle\MailChimpBundle\Placeholder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Availability resolver for marketing list synchronization status view block on marketing list view page.
 */
class MarketingListPlaceholderFilter
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Checks the object is an instance of a given class.
     *
     * @param MarketingList $marketingList
     * @return bool
     */
    public function isApplicableOnMarketingList($marketingList)
    {
        if ($marketingList instanceof MarketingList) {
            return (bool)$this->registry->getManager()
                ->getRepository('OroMailChimpBundle:StaticSegment')
                ->findOneBy(['marketingList' => $marketingList]);
        }

        return false;
    }
}
