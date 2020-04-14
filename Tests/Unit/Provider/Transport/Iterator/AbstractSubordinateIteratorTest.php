<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

class AbstractSubordinateIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $iterator;

    protected function setUp(): void
    {
        $this->iterator = $this->getMockBuilder(
            'Oro\\Bundle\\MailChimpBundle\\Provider\\Transport\\MailChimpClient'
        )->disableOriginalConstructor()->getMock();
    }

    /**
     * @param \Iterator $mainIterator
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function createIterator(\Iterator $mainIterator)
    {
        return $this->getMockForAbstractClass(
            'Oro\\Bundle\\MailChimpBundle\\Provider\\Transport\\Iterator\\AbstractSubordinateIterator',
            [$mainIterator]
        );
    }

    /**
     * @dataProvider optionsDataProvider
     * @param \Iterator $mainIterator
     * @param array $expectedValueMap
     * @param array $expected
     */
    public function testIteratorWorks($mainIterator, $expectedValueMap, $expected)
    {
        $iterator = $this->createIterator($mainIterator);

        $iterator->expects($this->exactly(count($expectedValueMap)))
            ->method('createSubordinateIterator')
            ->will($this->returnValueMap($expectedValueMap));

        $actual = [];
        foreach ($iterator as $key => $value) {
            $actual[$key] = $value;
        }
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider optionsDataProvider
     * @param \Iterator $mainIterator
     * @param array $expectedValueMap
     * @param array $expected
     */
    public function testIteratorRewindWorks($mainIterator, $expectedValueMap, $expected)
    {
        $iterator = $this->createIterator($mainIterator);

        $iterator->expects($this->exactly(count($expectedValueMap) * 2))
            ->method('createSubordinateIterator')
            ->will($this->returnValueMap($expectedValueMap));

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

    /**
     * @return array
     */
    public function optionsDataProvider()
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
