<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendedMergeVarTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExtendedMergeVar
     */
    protected $entity;

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
     * @param string $property
     * @param mixed $value
     * @param mixed $default
     */
    public function testSettersAndGetters($property, $value, $default = null)
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

    /**
     * @return array
     */
    public function settersAndGettersDataProvider()
    {
        return [
            ['staticSegment', $this->createMock('Oro\\Bundle\\MailChimpBundle\\Entity\\StaticSegment')],
            ['label', 'Dummy Label'],
            ['state', ExtendedMergeVar::STATE_SYNCED, ExtendedMergeVar::STATE_ADD]
        ];
    }

    /**
     * @dataProvider setNameDataProvider
     * @param mixed $value
     */
    public function testSetNameWhenInputIsWrong($value)
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Name must be not empty string.');

        $this->entity->setName($value);
    }

    /**
     * @return array
     */
    public function setNameDataProvider()
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
     * @param string $value
     * @param string $expected
     */
    public function testTagGenerationWithDifferentNameLength($value, $expected)
    {
        $this->entity->setName($value);

        self::assertEquals($expected, $this->entity->getTag());
    }

    /**
     * @return array
     */
    public function tagGenerationDataProvider()
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
