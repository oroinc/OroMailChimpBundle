<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\Segment;

use Oro\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionList;
use Oro\Bundle\MailChimpBundle\Model\Segment\ColumnDefinitionListFactory;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class ColumnDefinitionListFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $segment = $this->createMock(Segment::class);

        $marketingList = $this->createMock(MarketingList::class);
        $marketingList->expects(self::any())
            ->method('getSegment')
            ->willReturn($segment);

        $factory = new ColumnDefinitionListFactory();
        $object = $factory->create($marketingList);

        self::assertEquals(new ColumnDefinitionList($segment), $object);
    }
}
