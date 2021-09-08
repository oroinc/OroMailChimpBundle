<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;

/**
 * Mailchimp members iterator to retrieve records with 'update' status.
 */
class UpdatedMemberSyncIterator extends MemberSyncIterator
{
    /**
     * Runs subordinate iterator rewind.
     * It allows to read data after previously iterated results were processed with writer.
     *
     * {@inheritdoc}
     */
    public function valid()
    {
        if ($this->offset > 0 && !is_null($this->subordinateIterator)) {
            $this->subordinateIterator->rewind();
            $this->current = $this->read();
        }

        return !is_null($this->current);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->current = $this->read();
        if (!is_null($this->current)) {
            $this->offset += 1;
        }
    }

    /**
     * Return query builder instead of BufferedQueryResultIterator.
     *
     * {@inheritdoc}
     */
    protected function createSubordinateIterator($staticSegment)
    {
        return new QueryWithOptionsIterator(
            $this->getIteratorQueryBuilder($staticSegment),
            [
                'subscribers_list_id' => $staticSegment->getSubscribersList()->getId(),
                'has_first_name' => $this->hasFirstName,
                'has_last_name' => $this->hasLastName
            ]
        );
    }

    /**
     * Adds required fields and filters members that are to be updated into mailchimp.
     *
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(StaticSegment $staticSegment)
    {
        $qb = $this->getCommonIteratorQueryBuilder($staticSegment);

        $qb->addSelect(sprintf('%s.id as mailchimpMemberId', self::MEMBER_ALIAS))
            // Select only members that are to be updated to mailchimp
            ->andWhere($qb->expr()->eq(sprintf('%s.status', self::MEMBER_ALIAS), ':status'))
            ->setParameter('status', Member::STATUS_UPDATE);

        $groupBy = $this->getGroupBy($qb);
        if ($groupBy) {
            $groupBy[] = sprintf('%s.id', self::MEMBER_ALIAS);
            $qb->addGroupBy(implode(',', $groupBy));
        }

        return $qb;
    }
}
