<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

/**
 * Mailchimp member iterator.
 */
class ListsMembersSubordinateIterator extends AbstractSubscribersListIterator
{
    private array $options;

    public function __construct(\Iterator $mainIterator, MailChimpClient $client, $options = [])
    {
        $this->options = $options;
        parent::__construct($mainIterator, $client);
    }

    /**
     * @param SubscribersList $mainIteratorElement
     *
     * @return ListsMembersIterator
     */
    #[\Override]
    protected function createSubordinateIterator($mainIteratorElement): ListsMembersIterator
    {
        parent::assertSubscribersList($mainIteratorElement);

        $membersIterator = new ListsMembersIterator($this->client);
        $membersIterator->setListId($mainIteratorElement->getOriginId());
        $membersIterator->setOptions($this->options);

        return $membersIterator;
    }
}
