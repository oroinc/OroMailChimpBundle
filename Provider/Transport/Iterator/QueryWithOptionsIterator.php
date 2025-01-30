<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\ORM\QueryBuilder;

/**
 * Query builder iterator to support read once and given additional options appending to iteration result.
 */
class QueryWithOptionsIterator implements \Iterator
{
    public const RESULT_ITEMS = '__result_items';

    private const DEFAULT_BATCH_SIZE = 500;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var bool
     */
    private $readOnce;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var array
     */
    private $currentResult;

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(
        QueryBuilder $queryBuilder,
        array        $options = [],
        bool         $readOnce = true,
        ?int         $batchSize = null,
        ?int         $idlingNextSteps = null
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->readOnce = $readOnce;
        $this->options = $options;
        $this->batchSize = $batchSize ?: self::DEFAULT_BATCH_SIZE;
    }

    #[\Override]
    public function current(): array
    {
        if ($this->currentResult === null) {
            $this->fetchQueryResult();
        }

        return array_merge([self::RESULT_ITEMS => $this->currentResult], $this->options);
    }

    #[\Override]
    public function next(): void
    {
        if (!$this->readOnce) {
            $this->position++;
            $this->fetchQueryResult();
        }
    }

    #[\Override]
    public function key(): int
    {
        return $this->position;
    }

    #[\Override]
    public function valid(): bool
    {
        return (bool)count((array)$this->currentResult);
    }

    #[\Override]
    public function rewind(): void
    {
        $this->position = 0;
        $this->fetchQueryResult();
    }

    private function fetchQueryResult()
    {
        $this->queryBuilder
            ->setFirstResult($this->position * $this->batchSize)
            ->setMaxResults($this->batchSize);

        $this->currentResult = $this->queryBuilder->getQuery()->getArrayResult();
    }
}
