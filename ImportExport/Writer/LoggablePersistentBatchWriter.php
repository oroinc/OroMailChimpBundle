<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Oro\Bundle\IntegrationBundle\ImportExport\Writer\PersistentBatchWriter;

/**
 * Batch job's processed items logger.
 */
class LoggablePersistentBatchWriter extends PersistentBatchWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        parent::write($items);

        $this->logger->info(sprintf('%d items written', count($items)));
    }
}
