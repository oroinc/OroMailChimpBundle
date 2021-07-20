<?php

namespace Oro\Bundle\MailChimpBundle\Model\Segment;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Segment's column definition list provider.
 */
class ColumnDefinitionList implements ColumnDefinitionListInterface
{
    /** @var array */
    private $columns;

    public function __construct(Segment $segment)
    {
        $this->columns = $this->createColumnDefinitions(
            QueryDefinitionUtil::decodeDefinition($segment->getDefinition())
        );
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    private function createColumnDefinitions(?array $definition): array
    {
        if (null === $definition || empty($definition['columns'])) {
            return [];
        }

        $columnDefinitions = [];
        foreach ($definition['columns'] as $column) {
            if (!isset($column['name'], $column['label'])) {
                continue;
            }
            $columnDefinitions[] = ['name' => $column['name'], 'label' => $column['label']];
        }

        return $columnDefinitions;
    }
}
