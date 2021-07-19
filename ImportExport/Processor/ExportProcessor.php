<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Processor;

use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Step\StepExecutionAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextAwareInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Processor\ExportProcessor as BaseExportProcessor;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;

/**
 * Batch job processor to handle export with given(injected) export strategy.
 */
class ExportProcessor extends BaseExportProcessor implements StepExecutionAwareInterface
{
    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var StrategyInterface|ContextAwareInterface
     */
    protected $strategy;

    /**
     * @var ContextInterface
     */
    protected $importExportContext;

    public function setContextRegistry(ContextRegistry $contextRegistry)
    {
        $this->contextRegistry = $contextRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function setStepExecution(StepExecution $stepExecution)
    {
        $this->importExportContext = $this->contextRegistry->getByStepExecution($stepExecution);

        $this->setImportExportContext($this->importExportContext);
    }

    public function setStrategy(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
    }

    /**
     * {@inheritdoc}
     */
    public function process($item)
    {
        if ($this->strategy) {
            $this->strategy->setImportExportContext($this->importExportContext);

            $item = $this->strategy->process($item);
        }

        return $item;
    }
}
