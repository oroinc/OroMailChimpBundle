<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendedMergeVarTest extends \PHPUnit\Framework\TestCase
{
    private ExtendedMergeVar $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new ExtendedMergeVar();
    }

    public function testObjectInitialization()
    {
        $entity = new ExtendedMergeVar();

        self::assertEquals(ExtendedMergeVar::STATE_ADD, $entity->getState());
        self::assertEquals(ExtendedMergeVar::TAG_TEXT_FIELD_TYPE, $entity->getFieldType());
        self::assertFalse($entity->isRequired());
        self::assertNull($entity->getName());
        self::assertNull($entity->getLabel());
        self::assertNull($entity->getTag());
    }

    public function testGetId()
    {
        self::assertNull($this->entity->getId());

        $value = 8;
        ReflectionUtil::setId($this->entity, $value);
        self::assertSame($value, $this->entity->getId());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value, mixed $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        self::assertEquals(
            $default,
            $propertyAccessor->getValue($this->entity, $property)
        );

        $propertyAccessor->setValue($this->entity, $property, $value);

        self::assertEquals(
            $value,
            $propertyAccessor->getValue($this->entity, $property)
        );
    }

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['staticSegment', $this->createMock(StaticSegment::class)],
            ['label', 'Dummy Label'],
            ['state', ExtendedMergeVar::STATE_SYNCED, ExtendedMergeVar::STATE_ADD]
        ];
    }

    /**
     * @dataProvider setNameDataProvider
     */
    public function testSetNameWhenInputIsWrong(mixed $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Name must be not empty string.');

        $this->entity->setName($value);
    }

    public function setNameDataProvider(): array
    {
        return [
            [''],
            [123],
            [[]],
            [new \ArrayIterator([])]
        ];
    }

    public function testSetAndGetName()
    {
        $this->assertNull($this->entity->getName());
        $name = 'total';
        $expectedTag = ExtendedMergeVar::TAG_PREFIX . strtoupper($name);
        $this->entity->setName($name);

        self::assertEquals($name, $this->entity->getName());
        self::assertEquals($expectedTag, $this->entity->getTag());
    }

    /**
     * @dataProvider tagGenerationDataProvider
     */
    public function testTagGenerationWithDifferentNameLength(string $value, string $expected)
    {
        $this->entity->setName($value);

        self::assertEquals($expected, $this->entity->getTag());
    }

    public function tagGenerationDataProvider(): array
    {
        $prefix = ExtendedMergeVar::TAG_PREFIX;

        return [
            ['total', $prefix . 'TOTAL'],
            ['entity_total', $prefix . 'NTTY_TTL'],
            ['anyEntityAttr', $prefix . 'NYNTTYTT'],
            ['email', $prefix . 'EMAIL'],
            ['customer+Oro\Bundle\CustomerBundle\Entity\Customer::name', $prefix . 'NAME'],
            ['customer+Oro\Bundle\CustomerBundle\Entity\Customer::internal_rating', $prefix . 'NTRNL_RT'],
        ];
    }

    public function testIsAddState()
    {
        $this->entity->setState(ExtendedMergeVar::STATE_ADD);
        self::assertTrue($this->entity->isAddState());
    }

    public function testIsRemoveState()
    {
        $this->entity->setState(ExtendedMergeVar::STATE_REMOVE);
        self::assertTrue($this->entity->isRemoveState());
    }

    public function testSetSyncedState()
    {
        $this->entity->markSynced();
        self::assertEquals(ExtendedMergeVar::STATE_SYNCED, $this->entity->getState());
    }

    public function testSetDroppedState()
    {
        $this->entity->markDropped();
        self::assertEquals(ExtendedMergeVar::STATE_DROPPED, $this->entity->getState());
    }
}
