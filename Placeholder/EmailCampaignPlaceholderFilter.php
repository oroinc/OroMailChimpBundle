<?php

namespace Oro\Bundle\MailChimpBundle\Placeholder;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\MailChimpBundle\Transport\MailChimpTransport;

/**
 * Availability resolver for email campaign activity update buttons action button on campaign's page.
 */
class EmailCampaignPlaceholderFilter
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
     * @param EmailCampaign $entity
     * @return bool
     */
    public function isApplicableOnEmailCampaign($entity)
    {
        if ($entity instanceof EmailCampaign && $entity->getTransport() === MailChimpTransport::NAME) {
            $campaign = $this->registry->getManager()
                ->getRepository('OroMailChimpBundle:Campaign')
                ->findOneBy(['emailCampaign' => $entity]);
            return (bool) $campaign;
        } else {
            return false;
        }
    }
}
