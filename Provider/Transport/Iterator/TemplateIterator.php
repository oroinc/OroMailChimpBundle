<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Oro\Bundle\MailChimpBundle\Util\CallbackFilterIteratorCompatible;

/**
 * Maichimp template iterator
 */
class TemplateIterator implements \Iterator
{
    /**
     * @var MailChimpClient
     */
    protected $client;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @var \Iterator
     */
    protected $iterator;

    public function __construct(MailChimpClient $client, array $parameters = [])
    {
        $this->client = $client;
        $this->parameters = $parameters;
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    #[\Override]
    public function next(): void
    {
        $this->iterator->next();
    }

    #[\Override]
    public function key(): mixed
    {
        return $this->iterator->key();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        if (!$this->iterator) {
            $this->initIterator();
        }
        $this->iterator->rewind();
    }

    public function initIterator()
    {
        $templatesList = (array)$this->client->getTemplates($this->parameters);

        $this->iterator = new CallbackFilterIteratorCompatible(
            new FlattenIterator($templatesList, 'type', false),
            function (&$current) {
                if (is_array($current)) {
                    $current['origin_id'] = $current['id'];
                    unset($current['id']);
                }

                return true;
            }
        );
    }
}
