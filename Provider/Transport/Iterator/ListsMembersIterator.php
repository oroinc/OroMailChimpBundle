<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Exception\MailChimpClientException;

/**
 * Mailchimp member iterator.
 */
class ListsMembersIterator extends AbstractMailChimpIterator
{
    protected string $listId;
    protected array $options = [];

    public function setListId(string $listId): void
    {
        $this->listId = $listId;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     *
     * @throws MailChimpClientException
     */
    #[\Override]
    protected function getResult(): array
    {
        $options = array_merge(
            ['id' => $this->listId, 'count' => $this->batchSize, 'offset' => $this->offset / $this->batchSize],
            $this->options
        );

        $result = $this->client->getListMembers($options);

        return ['data' => $result['members'], 'total' => $result['total_items']];
    }
}
