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

        $segment = $this->getSegment();
        $segment->expects($this->once())->method('getDefinition')
            ->will($this->returnValue('incorrect_definition'));

        new ColumnDefinitionList($segment);
    }

    public function testGetColumnsWhenDefinitionHasNoColumns()
    {
        $segment = $this->getSegment();
        $definition = QueryDefinitionUtil::encodeDefinition(['filters' => []]);
        $segment->expects($this->once())->method('getDefinition')->will($this->returnValue($definition));

        $list = new ColumnDefinitionList($segment);

        $this->assertEmpty($list->getColumns());
    }

    public function testGetColumnsWhenColumnDefinitionIsIncorrect()
    {
        $segment = $this->getSegment();

        $definition = QueryDefinitionUtil::encodeDefinition([
            'columns' => [
                ['name' => 'email', 'func' => null],
                ['name' => 'total', 'label' => 'Total', 'func' => null]
            ],
            'filters' => []
        ]);

        $segment->expects($this->once())->method('getDefinition')->will($this->returnValue($definition));

        $list = new ColumnDefinitionList($segment);
        $columns = $list->getColumns();

        $this->assertCount(1, $columns);
        $column = current($columns);
        $this->assertThat($column['name'], $this->equalTo('total'));
        $this->assertThat($column['label'], $this->equalTo('Total'));
    }

    public function testGetColumns()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|Segment $segment */
        $segment = $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Segment')->getMock();

        $definition = QueryDefinitionUtil::encodeDefinition($this->getCorrectSegmentDefinition());

        $segment->expects($this->once())->method('getDefinition')->will($this->returnValue($definition));

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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Segment
     */
    protected function getSegment()
    {
        return $this->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Segment')->getMock();
    }

    /**
     * @return array
     */
    protected function getCorrectSegmentDefinition()
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
