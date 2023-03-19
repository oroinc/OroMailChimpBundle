<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SegmentTest extends \PHPUnit\Framework\TestCase
{
    private StaticSegment $entity;

    protected function setUp(): void
    {
        $this->entity = new StaticSegment();
    }

    public function testId()
    {
        $this->assertNull($this->entity->getId());
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
            ['name', 'segment'],
            ['originId', 123456789],
            ['channel', $this->createMock(Channel::class)],
            ['marketingList', $this->createMock(MarketingList::class)],
            ['subscribersList', $this->createMock(SubscribersList::class)],
            ['subscribersList', $this->createMock(SubscribersList::class)],
            ['owner', $this->createMock(Organization::class)],
            ['syncStatus', 1],
            ['lastSynced', new \DateTime()],
            ['remoteRemove', true, false],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];
    }

    public function testPrePersist()
    {
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->prePersist();

        $this->assertInstanceOf(\DateTime::class, $this->entity->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());

        $expectedCreated = $this->entity->getCreatedAt();
        $expectedUpdated = $this->entity->getUpdatedAt();

        $this->entity->prePersist();

        $this->assertSame($expectedCreated, $this->entity->getCreatedAt());
        $this->assertSame($expectedUpdated, $this->entity->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->entity->getUpdatedAt());
        $this->entity->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->entity->getUpdatedAt());
    }

    public function testGetExtendedMergeVars()
    {
        $this->assertEmpty($this->entity->getExtendedMergeVars());

        $var = new ExtendedMergeVar();
        $this->entity->addExtendedMergeVar($var);

        $extendedMergeVars = $this->entity->getExtendedMergeVars();

        $this->assertNotEmpty($extendedMergeVars);
        $this->assertContains($var, $extendedMergeVars);

        $this->entity->removeExtendedMergeVar($var);

        $this->assertEmpty($this->entity->getExtendedMergeVars());
    }

    public function testGetExtendedMergeVarsWithFilterByState()
    {
        $this->assertEmpty($this->entity->getExtendedMergeVars());

        $var1 = new ExtendedMergeVar();
        $var2 = new ExtendedMergeVar();

        $var1->markSynced();
        $var2->markDropped();

        $this->entity->addExtendedMergeVar($var1);
        $this->entity->addExtendedMergeVar($var2);

        $extendedMergeVars = $this->entity->getExtendedMergeVars([ExtendedMergeVar::STATE_SYNCED]);

        $this->assertCount(1, $extendedMergeVars);
        $this->assertContainsOnly(ExtendedMergeVar::class, $extendedMergeVars);
    }

    public function testGetSyncedExtendedMergeVars()
    {
        $this->assertEmpty($this->entity->getExtendedMergeVars());

        $var1 = new ExtendedMergeVar();
        $var2 = new ExtendedMergeVar();

        $var1->markSynced();
        $var2->markDropped();

        $this->entity->addExtendedMergeVar($var1);
        $this->entity->addExtendedMergeVar($var2);

        $extendedMergeVars = $this->entity->getSyncedExtendedMergeVars();

        $this->assertCount(1, $extendedMergeVars);
        $this->assertContainsOnly(ExtendedMergeVar::class, $extendedMergeVars);
    }
}
