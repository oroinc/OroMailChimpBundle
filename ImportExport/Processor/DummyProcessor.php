<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Processor;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorInterface;

/**
 * Dummy batch job processor.
 */
class DummyProcessor implements ProcessorInterface
{
    #[\Override]
    public function process($item)
    {
        return $item;
    }
}
