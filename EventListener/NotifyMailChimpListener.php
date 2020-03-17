<?php

namespace Oro\Bundle\MailChimpBundle\EventListener;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MarketingListBundle\Event\UpdateMarketingListEvent;

/**
 * Event listener to schedule mailchimp static segment sync in case if related marketing list was updated
 */
class NotifyMailChimpListener
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param UpdateMarketingListEvent $event
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function onMarketingListChange(UpdateMarketingListEvent $event)
    {
        $marketingLists = $event->getMarketingLists();
        $em = $this->doctrineHelper->getEntityManager(StaticSegment::class);
        $changedStaticSegments = [];

        foreach ($marketingLists as $marketingList) {
            $staticSegments = $em
                ->getRepository(StaticSegment::class)
                ->findBy(['marketingList' => $marketingList]);

            foreach ($staticSegments as $staticSegment) {
                $staticSegment->setSyncStatus(StaticSegment::STATUS_SCHEDULED_BY_CHANGE);
                $changedStaticSegments[] = $staticSegment;
            }
        }

        $em->flush($changedStaticSegments);
    }
}
