<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

/**
 * Mailchimp static segment iterator.
 */
class StaticSegmentListIterator extends AbstractSubscribersListIterator
{
    #[\Override]
    protected function createSubordinateIterator($subscribersList)
    {
        parent::assertSubscribersList($subscribersList);

        $segmentIterator = new StaticSegmentIterator($this->client);
        $segmentIterator->setSubscriberListId($subscribersList->getOriginId());

        return $segmentIterator;
    }
}
