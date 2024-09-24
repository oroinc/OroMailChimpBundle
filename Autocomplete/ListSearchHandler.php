<?php

namespace Oro\Bundle\MailChimpBundle\Autocomplete;

/**
 * Search handler to search among imported mailchimp's subscribers lists
 * by given string with search criteria and integration channel ID.
 */
class ListSearchHandler extends IntegrationAwareSearchHandler
{
    #[\Override]
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        list($searchTerm, $channelId) = explode(';', $search);

        $queryBuilder = $this->entityRepository->createQueryBuilder('e');
        $queryBuilder
            ->where($queryBuilder->expr()->like('LOWER(e.name)', ':searchTerm'))
            ->andWhere('e.channel = :channel')
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%')
            ->setParameter('channel', (int)$channelId)
            ->addOrderBy('e.name', 'ASC')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $query = $this->aclHelper->apply($queryBuilder, 'VIEW');

        return $query->getResult();
    }
}
