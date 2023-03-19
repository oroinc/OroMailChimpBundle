<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\ImportExport\Reade;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\MailChimpBundle\ImportExport\Reader\StaticSegmentReader;
use Oro\Component\Testing\ReflectionUtil;

class StaticSegmentReaderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var StaticSegmentReader */
    private $reader;

    protected function setUp(): void
    {
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->reader = new StaticSegmentReader($this->contextRegistry, $this->doctrineHelper, 'Acme\Demo\TestClass');
    }

    public function testCloseOnNonSelfGeneratedIterator()
    {
        $iterator = $this->createMock(\Iterator::class);
        $this->reader->setSourceIterator($iterator);

        $this->reader->close();

        $this->assertSame($iterator, $this->reader->getSourceIterator());
    }

    public function testCloseOnSelfGeneratedIterator()
    {
        ReflectionUtil::setPropertyValue($this->reader, 'isSelfCreatedIterator', true);

        $iterator = $this->createMock(\Iterator::class);
        $this->reader->setSourceIterator($iterator);

        $this->reader->close();

        $this->assertNull($this->reader->getSourceIterator());
    }
}
