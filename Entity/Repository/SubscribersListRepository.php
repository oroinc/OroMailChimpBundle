<?php

namespace Oro\Bundle\MailChimpBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;

/**
 * Mailchimp subscribers list entity repository class.
 */
class SubscribersListRepository extends EntityRepository
{
    /**
     * Gets buffered query result iterator for all subscriber lists with segments
     *
     * @param Channel $channel
     * @return \Iterator
     */
    public function getUsedSubscribersListIterator(Channel $channel)
    {
        $queryBuilder = $this
            ->createQueryBuilder('subscribersList')
            ->select('subscribersList')
            ->join(
                StaticSegment::class,
                'staticSegment',
                Join::WITH,
                'staticSegment.subscribersList = subscribersList.id'
            )
            ->where('subscribersList.channel = :channel')
            ->setParameter('channel', $channel);

        return new BufferedIdentityQueryResultIterator($queryBuilder);
    }
}
