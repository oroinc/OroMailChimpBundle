<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\ImportExport\Reade;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\MailChimpBundle\ImportExport\Reader\StaticSegmentReader;
use Oro\Component\Testing\ReflectionUtil;

class StaticSegmentReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $contaxtRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var StaticSegmentReader */
    protected $reader;

    protected function setUp(): void
    {
        $this->contaxtRegistry = $this->getMockBuilder(ContextRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reader = new StaticSegmentReader($this->contaxtRegistry, $this->doctrineHelper, 'Acme\Demo\TestClass');
    }

    public function testCloseOnNonSelfGeneratedIterator()
    {
        $iterator = $this->createMock('\Iterator');
        $this->reader->setSourceIterator($iterator);

        $this->reader->close();

        $this->assertSame($iterator, $this->reader->getSourceIterator());
    }

    public function testCloseOnSelfGeneratedIterator()
    {
        ReflectionUtil::setPropertyValue($this->reader, 'isSelfCreatedIterator', true);

        $iterator = $this->createMock('\Iterator');
        $this->reader->setSourceIterator($iterator);

        $this->reader->close();

        $this->assertNull($this->reader->getSourceIterator());
    }
}
