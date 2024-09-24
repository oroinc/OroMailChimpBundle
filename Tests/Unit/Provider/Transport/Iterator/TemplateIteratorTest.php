<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\TemplateIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;

class TemplateIteratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MailChimpClient|\PHPUnit\Framework\MockObject\MockObject */
    private $client;

    /** @var TemplateIterator */
    private $iterator;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = $this->getMockBuilder(MailChimpClient::class)
            ->disableOriginalConstructor()
            ->addMethods(['getTemplates'])
            ->getMock();

        $this->iterator = new TemplateIterator($this->client);
    }

    public function testIterator()
    {
        $rawTemplates = [
            'user' => [
                ['id' => 1, 'name' => 'template1'],
                ['id' => 2, 'name' => 'template2'],
            ],
            'gallery' => [
                ['id' => 3, 'name' => 'template3'],
            ]
        ];
        $expected = [
            ['origin_id' => 1, 'name' => 'template1', 'type' => 'user'],
            ['origin_id' => 2, 'name' => 'template2', 'type' => 'user'],
            ['origin_id' => 3, 'name' => 'template3', 'type' => 'gallery'],
        ];

        $this->client->expects($this->once())
            ->method('getTemplates')
            ->with($this->isType('array'))
            ->willReturn($rawTemplates);

        $actual = [];
        foreach ($this->iterator as $key => $value) {
            $actual[$key] = $value;
        }

        $this->assertEquals($expected, $actual);
    }
}
