<?php

namespace Oro\Bundle\MailChimpBundle\Entity\EntityListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;

/**
 * ORM event listener to remove mailchimp DB items when related integration is deleted.
 */
class ChannelListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function preRemove(Channel $channel)
    {
        $this->deleteRelatedMarketingListEmails($channel);
    }

    private function deleteRelatedMarketingListEmails(Channel $channel)
    {
        /** @var QueryBuilder $emailQueryBuilder */
        $emailQueryBuilder = $this->doctrine->getManagerForClass(MarketingListEmail::class)
            ->createQueryBuilder();
        $segmentQueryBuilder = $this->doctrine->getManagerForClass(StaticSegment::class)
            ->getRepository(StaticSegment::class)->createQueryBuilder('segment');

        $segmentQueryBuilder
            ->select('IDENTITY(segment.marketingList)')
            ->where($segmentQueryBuilder->expr()->eq('IDENTITY(segment.channel)', ':channel'));

        $emailQueryBuilder
            ->delete(MarketingListEmail::class, 'email')
            ->where($emailQueryBuilder->expr()->in('IDENTITY(email.marketingList)', $segmentQueryBuilder->getDQL()))
            ->setParameter(':channel', $channel->getId());

        $emailQueryBuilder->getQuery()->execute();
    }
}
