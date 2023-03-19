<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\MergeVar;

use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarFields;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarInterface;

class MergeVarFieldsTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEmailFieldNotFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isEmail')
            ->willReturn(false);

        $mergeVarFields = new MergeVarFields([$field]);

        $this->assertNull($mergeVarFields->getEmail());
    }

    public function testGetEmailFieldFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isEmail')
            ->willReturn(false);

        $foundField = $this->createMock(MergeVarInterface::class);
        $foundField->expects($this->once())
            ->method('isEmail')
            ->willReturn(true);

        $mergeVarFields = new MergeVarFields([$field, $foundField]);

        $this->assertSame($foundField, $mergeVarFields->getEmail());
    }

    public function testGetPhoneFieldNotFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isPhone')
            ->willReturn(false);

        $mergeVarFields = new MergeVarFields([$field]);

        $this->assertNull($mergeVarFields->getPhone());
    }

    public function testGetPhoneFieldFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isPhone')
            ->willReturn(false);

        $foundField = $this->createMock(MergeVarInterface::class);
        $foundField->expects($this->once())
            ->method('isPhone')
            ->willReturn(true);

        $mergeVarFields = new MergeVarFields([$field, $foundField]);

        $this->assertSame($foundField, $mergeVarFields->getPhone());
    }

    public function testGetFirstNameFieldNotFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isFirstName')
            ->willReturn(false);

        $mergeVarFields = new MergeVarFields([$field]);

        $this->assertNull($mergeVarFields->getFirstName());
    }

    public function testGetFirstNameFieldFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isFirstName')
            ->willReturn(false);

        $foundField = $this->createMock(MergeVarInterface::class);
        $foundField->expects($this->once())
            ->method('isFirstName')
            ->willReturn(true);

        $mergeVarFields = new MergeVarFields([$field, $foundField]);

        $this->assertSame($foundField, $mergeVarFields->getFirstName());
    }

    public function testGetLastNameFieldNotFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isLastName')
            ->willReturn(false);

        $mergeVarFields = new MergeVarFields([$field]);

        $this->assertNull($mergeVarFields->getLastName());
    }

    public function testGetLastNameFieldFound()
    {
        $field = $this->createMock(MergeVarInterface::class);
        $field->expects($this->once())
            ->method('isLastName')
            ->willReturn(false);

        $foundField = $this->createMock(MergeVarInterface::class);
        $foundField->expects($this->once())
            ->method('isLastName')
            ->willReturn(true);

        $mergeVarFields = new MergeVarFields([$field, $foundField]);

        $this->assertSame($foundField, $mergeVarFields->getLastName());
    }
}
