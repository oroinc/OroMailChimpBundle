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

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $options
     * @param bool $readOnce
     * @param null $batchSize
     */
    public function __construct(
        QueryBuilder $queryBuilder,
        array $options = [],
        bool $readOnce = true,
        int $batchSize = null,
        int $idlingNextSteps = null
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->readOnce = $readOnce;
        $this->options = $options;
        $this->batchSize = $batchSize ?: self::DEFAULT_BATCH_SIZE;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        if ($this->currentResult === null) {
            $this->fetchQueryResult();
        }

        return array_merge([self::RESULT_ITEMS => $this->currentResult], $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (!$this->readOnce) {
            $this->position++;
            $this->fetchQueryResult();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return (bool)count((array)$this->currentResult);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
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
