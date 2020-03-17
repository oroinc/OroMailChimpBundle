<?php

namespace Oro\Bundle\MailChimpBundle\Model\Segment;

/**
 * Interface for segment's column definition list provider.
 */
interface ColumnDefinitionListInterface
{
    /**
     * @return array
     */
    public function getColumns();
}
