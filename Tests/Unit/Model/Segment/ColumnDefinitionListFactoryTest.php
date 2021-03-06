<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\Segment;

use Oro\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionListFactory;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class ColumnDefinitionListFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ColumnDefinitionListFactory
     */
    protected $factory;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|MarketingList
     */
    protected $marketingList;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Segment
     */
    protected $segment;

    protected function setUp()
    {
        $this->marketingList = $this
            ->getMockBuilder('Oro\Bundle\MarketingListBundle\Entity\MarketingList')
            ->disableOriginalConstructor()
            ->getMock();
        $this->segment = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Entity\Segment')
            ->disableOriginalConstructor()
            ->getMock();
        $this->marketingList->expects($this->any())->method('getSegment')->will($this->returnValue($this->segment));
        $this->factory = new ColumnDefinitionListFactory();
    }

    protected function tearDown()
    {
        unset($this->marketingList);
        unset($this->segment);
        unset($this->factory);
    }

    public function testCreate()
    {
        $object = $this->factory->create($this->marketingList);

        $this->assertInstanceOf('Oro\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionList', $object);
    }
}
