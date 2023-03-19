<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\Segment;

use Oro\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionList;
use Oro\Bundle\QueryDesignerBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class ColumnDefinitionListTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWhenJsonRepresentationIsIncorrect()
    {
        $this->expectException(InvalidConfigurationException::class);

        $segment = $this->createMock(Segment::class);
        $segment->expects($this->once())
            ->method('getDefinition')
            ->willReturn('incorrect_definition');

        new ColumnDefinitionList($segment);
    }

    public function testGetColumnsWhenDefinitionHasNoColumns()
    {
        $segment = $this->createMock(Segment::class);
        $definition = QueryDefinitionUtil::encodeDefinition(['filters' => []]);
        $segment->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $list = new ColumnDefinitionList($segment);

        $this->assertEmpty($list->getColumns());
    }

    public function testGetColumnsWhenColumnDefinitionIsIncorrect()
    {
        $segment = $this->createMock(Segment::class);

        $definition = QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'email', 'func' => null],
                ['name' => 'total', 'label' => 'Total', 'func' => null]
            ],
            'filters' => []
        ]);

        $segment->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $list = new ColumnDefinitionList($segment);
        $columns = $list->getColumns();

        $this->assertCount(1, $columns);
        $column = current($columns);
        $this->assertThat($column['name'], $this->equalTo('total'));
        $this->assertThat($column['label'], $this->equalTo('Total'));
    }

    public function testGetColumns()
    {
        $segment = $this->createMock(Segment::class);

        $definition = QueryDefinitionUtil::encodeDefinition($this->getCorrectSegmentDefinition());

        $segment->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);

        $list = new ColumnDefinitionList($segment);

        $columns = $list->getColumns();

        $this->assertCount(2, $columns);

        $column1 = reset($columns);
        $column2 = next($columns);

        $this->assertArrayHasKey('name', $column1);
        $this->assertArrayHasKey('label', $column2);
        $this->assertThat($column1['name'], $this->equalTo('email'));
        $this->assertThat($column1['label'], $this->equalTo('Email'));
        $this->assertThat($column2['name'], $this->equalTo('total'));
        $this->assertThat($column2['label'], $this->equalTo('Total'));
    }

    private function getCorrectSegmentDefinition(): array
    {
        return [
            'columns' => [
                ['name' => 'email', 'label' => 'Email', 'func' => null],
                ['name' => 'total', 'label' => 'Total', 'func' => null]
            ],
            'filters' => []
        ];
    }
}
