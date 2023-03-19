<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\AbstractSubordinateIterator;

class AbstractSubordinateIteratorTest extends \PHPUnit\Framework\TestCase
{
    private function createIterator(\Iterator $mainIterator): \Iterator|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->getMockForAbstractClass(AbstractSubordinateIterator::class, [$mainIterator]);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testIteratorWorks(\Iterator $mainIterator, array $expectedValueMap, array $expected)
    {
        $iterator = $this->createIterator($mainIterator);

        $iterator->expects($this->exactly(count($expectedValueMap)))
            ->method('createSubordinateIterator')
            ->willReturnMap($expectedValueMap);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider optionsDataProvider
     */
    public function testIteratorRewindWorks(\Iterator $mainIterator, array $expectedValueMap, array $expected)
    {
        $iterator = $this->createIterator($mainIterator);

        $iterator->expects($this->exactly(count($expectedValueMap) * 2))
            ->method('createSubordinateIterator')
            ->willReturnMap($expectedValueMap);

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);

        // Rewind and iterate once again
        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    public function optionsDataProvider(): array
    {
        return [
            'with content' => [
                'mainIterator' => new \ArrayIterator([100, 200, 300, 400]),
                'expectedValueMap' => [
                    [100, new \ArrayIterator(['a', 'b'])],
                    [200, new \ArrayIterator(['c', 'd'])],
                    [300, new \ArrayIterator(['e'])],
                    [400, new \ArrayIterator(['f'])]
                ],
                'expected' => ['a', 'b', 'c', 'd', 'e', 'f'],
            ],
            'empty main iterator' => [
                'mainIterator' => new \ArrayIterator(),
                'expectedValueMap' => [],
                'expected' => [],
            ],
            'empty subordinate iterators' => [
                'mainIterator' => new \ArrayIterator([100, 200, 300, 400]),
                'expectedValueMap' => [
                    [100, new \ArrayIterator()],
                    [200, new \ArrayIterator()],
                    [300, new \ArrayIterator()],
                    [400, new \ArrayIterator()]
                ],
                'expected' => [],
            ],
        ];
    }
}
