<?php

namespace Oro\Bundle\MailChimpBundle\Entity\EntityListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;

/**
 * ORM event listener to remove maichimp DB items when related integration is deleted.
 */
class ChannelListener
{
    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function preRemove(Channel $channel)
    {
        $this->deleteRelatedMarketingListEmails($channel);
    }

    private function deleteRelatedMarketingListEmails(Channel $channel)
    {
        /** @var QueryBuilder $emailQueryBuilder */
        $emailQueryBuilder = $this->registry->getManagerForClass(MarketingListEmail::class)
            ->createQueryBuilder('email');
        $segmentQueryBuilder = $this->registry->getManagerForClass(StaticSegment::class)
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
