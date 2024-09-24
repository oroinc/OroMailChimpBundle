<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

/**
 * Iterator for data structures like ['item_key' => []]
 * where item_key will be passed to all children with some given key
 */
class FlattenIterator implements \Iterator
{
    /**
     * @var \Iterator
     */
    protected $toIterate;

    /**
     * @var string
     */
    protected $keyToElementName;

    /**
     * @var string
     */
    protected $toIterateKey;

    /**
     * @var \Iterator
     */
    protected $subIterate;

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var bool
     */
    protected $processEmpty;

    /**
     * @var int
     */
    protected $dataLevel;

    /**
     * @param \Iterator|array $toIterate
     * @param string $keyToElementName
     * @param bool $processEmpty
     * @param int $dataLevel
     */
    public function __construct($toIterate, $keyToElementName, $processEmpty = false, $dataLevel = 1)
    {
        if (!$toIterate instanceof \Iterator && is_array($toIterate)) {
            $toIterate = new \ArrayIterator($toIterate);
        }
        $this->toIterate = $toIterate;
        $this->keyToElementName = $keyToElementName;
        $this->processEmpty = $processEmpty;
        $this->dataLevel = $dataLevel;
    }

    #[\Override]
    public function current(): mixed
    {
        $current = $this->subIterate->current();
        if ($this->dataLevel == 1) {
            $current[$this->keyToElementName] = $this->toIterateKey;
        }

        return $current;
    }

    #[\Override]
    public function next(): void
    {
        if ($this->subIterate && $this->subIterate->valid()) {
            $this->subIterate->next();
        }
        if (!$this->subIterate->valid()) {
            $this->toIterate->next();
            $this->initializeSubIterate();
        }

        ++$this->position;
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function valid(): bool
    {
        return ($this->subIterate && $this->subIterate->valid()) || $this->toIterate->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;
        $this->toIterateKey = null;

        $this->toIterate->rewind();
        $this->initializeSubIterate();
    }

    /**
     * Initialize sub-iterator.
     */
    protected function initializeSubIterate()
    {
        if ($this->toIterate->valid()) {
            $this->toIterateKey = $this->toIterate->key();
            $currentIterator = new \ArrayIterator((array)$this->toIterate->current());
            if ($this->dataLevel == 1) {
                $this->subIterate = $currentIterator;
            } else {
                $this->subIterate = new self(
                    $currentIterator,
                    $this->keyToElementName,
                    $this->processEmpty,
                    $this->dataLevel - 1
                );
            }
            $this->subIterate->rewind();
        } else {
            $this->toIterateKey = null;
            $this->subIterate = null;
        }
    }
}
