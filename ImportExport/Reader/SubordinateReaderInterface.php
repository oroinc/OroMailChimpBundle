<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Reader;

/**
 * Interface to implement determining that subordinate iterator was changed.
 */
interface SubordinateReaderInterface
{
    /**
     * Determines that subordinate iterator was changed
     *
     * @return bool
     */
    public function writeRequired();
}
