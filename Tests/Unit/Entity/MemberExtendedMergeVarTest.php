<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\MailChimpBundle\Entity\MemberExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Component\Testing\ReflectionUtil;

class MemberExtendedMergeVarTest extends \PHPUnit\Framework\TestCase
{
    private MemberExtendedMergeVar $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new MemberExtendedMergeVar();
    }

    public function testObjectInitialization()
    {
        $entity = new MemberExtendedMergeVar();

        $this->assertEquals(MemberExtendedMergeVar::STATE_ADD, $entity->getState());
        $this->assertNull($entity->getStaticSegment());
        $this->assertNull($entity->getMember());
        $this->assertEmpty($entity->getMergeVarValues());
        $this->assertEmpty($entity->getMergeVarValuesContext());
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $value = 8;
        ReflectionUtil::setId($this->entity, $value);
        $this->assertSame($value, $this->entity->getId());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value, mixed $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->assertEquals(
            $default,
            $propertyAccessor->getValue($this->entity, $property)
        );

        $propertyAccessor->setValue($this->entity, $property, $value);

        $this->assertEquals(
            $value,
            $propertyAccessor->getValue($this->entity, $property)
        );
    }

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['staticSegment', $this->createMock(StaticSegment::class)],
            ['state', MemberExtendedMergeVar::STATE_SYNCED, MemberExtendedMergeVar::STATE_ADD],
            ['merge_var_values', ['var' => 'value'], []],
            ['merge_var_values_context', ['context'], []]
        ];
    }

    public function testIsAddState()
    {
        $this->entity->setState(MemberExtendedMergeVar::STATE_ADD);
        $this->assertTrue($this->entity->isAddState());
    }

    public function testSetSyncedState()
    {
        $this->entity->markSynced();
        $this->assertEquals(MemberExtendedMergeVar::STATE_SYNCED, $this->entity->getState());
    }

    /**
     * @dataProvider invalidMergeVarNamesAndValues
     */
    public function testAddMergeVarValueWhenNameOrValueIsInvalid(string|array $name, string|array $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Merge name and value should be not empty strings.');

        $this->entity->addMergeVarValue($name, $value);
    }

    public function invalidMergeVarNamesAndValues(): array
    {
        return [
            ['', ''],
            ['', 'value'],
            ['name', ''],
            ['name', []],
            [[], 'value']
        ];
    }

    /**
     * @dataProvider validMergeVarNamesAndValues
     */
    public function testAddMergeVarValue(
        array $initialMergeVarValues,
        array $newMergeVarValues,
        string $initialState,
        string $expectedState
    ) {
        foreach ($initialMergeVarValues as $name => $value) {
            $this->entity->addMergeVarValue($name, $value);
        }

        $this->entity->setState($initialState);

        foreach ($newMergeVarValues as $name => $value) {
            $this->entity->addMergeVarValue($name, $value);
        }

        $this->assertEquals($expectedState, $this->entity->getState());
    }

    public function validMergeVarNamesAndValues(): array
    {
        return [
            [
                ['name' => 'value'], ['name_new' => 'value', 'name' => 'value_new'], 'sync', 'add'
            ],
            [
                ['name' => 'value'], ['name_new' => 'value'], 'sync', 'add'
            ],
            [
                ['name' => 'value'], ['name' => 'value_new'], 'sync', 'add'
            ],
            [
                ['name' => 'value'], ['name' => 'value'], 'sync', 'sync'
            ]
        ];
    }
}
