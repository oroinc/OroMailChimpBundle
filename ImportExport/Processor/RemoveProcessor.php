<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\EntityNameAwareInterface;
use Oro\Bundle\ImportExportBundle\Processor\StepExecutionAwareProcessor;

/**
 * Generic batch job processor to handle entities removal.
 */
class RemoveProcessor implements StepExecutionAwareProcessor, EntityNameAwareInterface
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $field;

    public function __construct(
        ContextRegistry $contextRegistry,
        DoctrineHelper $doctrineHelper
    ) {
        $this->contextRegistry = $contextRegistry;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process($item)
    {
        if (is_array($item)) {
            $this->updateContext($item);
        }

        return $item;
    }

    protected function updateContext(array $item)
    {
        $context = $this->contextRegistry->getByStepExecution($this->stepExecution);
        $toDelete = (int)$context->getDeleteCount() + $this->getItemsToRemoveCount($item);
        $context->setValue('delete_count', $toDelete);
    }

    /**
     * @param array $item
     * @return int
     */
    protected function getItemsToRemoveCount(array $item)
    {
        $qb = $this->createQueryBuilder($item);
        $result = $qb->getQuery()->getArrayResult();
        if ($result) {
            return (int)$result[0]['itemsCount'];
        }

        return 0;
    }

    /**
     * @param array $item
     * @return QueryBuilder
     */
    protected function createQueryBuilder(array $item)
    {
        $em = $this->doctrineHelper->getEntityManager($this->entityName);
        $identifierFieldName = $this->doctrineHelper->getSingleEntityIdentifierFieldName($this->entityName);
        $qb = $em->createQueryBuilder();
        $qb->select('COUNT(e.' . $identifierFieldName . ') as itemsCount')
            ->from($this->entityName, 'e');
        if (array_key_exists($this->field, $item)) {
            $qb->andWhere($qb->expr()->notIn('e.' . $this->field, ':items'))
                ->setParameter('items', (array)$item[$this->field]);
        }
        // Workaround to limit by channel. Channel is not available in second step context.
        if (array_key_exists('channel', $item)) {
            $qb->andWhere($qb->expr()->eq('e.channel', ':channel'))
                ->setParameter('channel', $item['channel']);
        }

        return $qb;
    }

    #[\Override]
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->stepExecution = $stepExecution;
    }

    #[\Override]
    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    /**
     * Set field name which will be used for search of entities to remove.
     *
     * @param string $field
     */
    public function setSearchField($field)
    {
        $this->field = $field;
    }
}
