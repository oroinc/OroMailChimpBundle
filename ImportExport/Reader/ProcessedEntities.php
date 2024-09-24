<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Reader;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Reader\AbstractReader;

/**
 * Batch job's processed entities reader.
 */
class ProcessedEntities extends AbstractReader
{
    #[\Override]
    public function read()
    {
        /** @var JobExecution $jobExecution */
        $jobExecution = $this->stepExecution->getJobExecution();
        $processedEntities = $jobExecution->getExecutionContext()->get('processed_entities');
        // Mark processed_entities as read
        $jobExecution->getExecutionContext()->put('processed_entities', false);

        // For processed_entities
        // null - no items were returned by API,
        // false - them are already read
        if ($processedEntities) {
            return $processedEntities;
        } elseif ($processedEntities === null) {
            // In case when there are no campaigns returned - remove all saved campaigns
            return ['channel' => $jobExecution->getExecutionContext()->get('channel')];
        } else {
            return null;
        }
    }
}
