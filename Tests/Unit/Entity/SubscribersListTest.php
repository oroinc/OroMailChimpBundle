<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarFieldsInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class SubscribersListTest extends \PHPUnit\Framework\TestCase
{
    private SubscribersList $target;

    #[\Override]
    protected function setUp(): void
    {
        $this->target = new SubscribersList();
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters(string $property, mixed $value)
    {
        $method = 'set' . ucfirst($property);
        $result = $this->target->$method($value);

        $this->assertInstanceOf(get_class($this->target), $result);
        $this->assertEquals($value, $this->target->{'get' . $property}());
    }

    /**
     * @dataProvider settersAndIsDataProvider
     */
    public function testSettersAndIs(string $property, mixed $value)
    {
        $method = 'set' . ucfirst($property);
        $result = $this->target->$method($value);

        $this->assertInstanceOf(get_class($this->target), $result);
        $this->assertEquals($value, $this->target->{'is' . $property}());
    }

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['originId', 123456789],
            ['channel', $this->createMock(Channel::class)],
            ['owner', $this->createMock(Organization::class)],
            ['webId', 12],
            ['name', 'string'],
            ['defaultFromName', 'string'],
            ['defaultFromName', null],
            ['defaultFromEmail', 'string'],
            ['defaultFromEmail', null],
            ['defaultSubject', 'string'],
            ['defaultSubject', null],
            ['defaultLanguage', 'string'],
            ['defaultLanguage', null],
            ['listRating', 1.3],
            ['listRating', null],
            ['subscribeUrlShort', 'string'],
            ['subscribeUrlShort', null],
            ['subscribeUrlLong', 'string'],
            ['subscribeUrlLong', null],
            ['beamerAddress', 'string'],
            ['beamerAddress', null],
            ['visibility', 'string'],
            ['visibility', null],
            ['memberCount', 3.4],
            ['memberCount', null],
            ['unsubscribeCount', 4.4],
            ['unsubscribeCount', null],
            ['cleanedCount', 43.4],
            ['cleanedCount', null],
            ['memberCountSinceSend', 433.4],
            ['memberCountSinceSend', null],
            ['unsubscribeCountSinceSend', 33.4],
            ['unsubscribeCountSinceSend', null],
            ['cleanedCountSinceSend', 333.4],
            ['cleanedCountSinceSend', null],
            ['campaignCount', 13.43],
            ['campaignCount', null],
            ['groupingCount', 123.43],
            ['groupingCount', null],
            ['groupCount', 4321.43],
            ['groupCount', null],
            ['mergeVarCount', 41.43],
            ['mergeVarCount', null],
            ['avgSubRate', 87.43],
            ['avgSubRate', null],
            ['avgUsubRate', 97.43],
            ['avgUsubRate', null],
            ['targetSubRate', 7.12],
            ['targetSubRate', null],
            ['openRate', 72.12],
            ['openRate', null],
            ['clickRate', 2.12],
            ['clickRate', null],
            [
                'mergeVarFields',
                $this->createMock(MergeVarFieldsInterface::class)
            ],
            ['mergeVarConfig', [['foo' => 'bar']]],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAt', null],
        ];
    }

    public function settersAndIsDataProvider(): array
    {
        return [
            ['emailTypeOption', true],
            ['useAwesomeBar', true],
        ];
    }

    public function testSetMergeVarConfigResetsMergeVarFields()
    {
        $mergeVarsFields = $this->createMock(MergeVarFieldsInterface::class);

        $this->target->setMergeVarFields($mergeVarsFields);

        $this->target->setMergeVarConfig([]);

        $this->assertNull($this->target->getMergeVarFields());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->target->getCreatedAt());
        $this->assertNull($this->target->getUpdatedAt());

        $this->target->prePersist();

        $this->assertInstanceOf(\DateTime::class, $this->target->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());

        $expectedCreated = $this->target->getCreatedAt();
        $expectedUpdated = $this->target->getUpdatedAt();

        $this->target->prePersist();

        $this->assertSame($expectedCreated, $this->target->getCreatedAt());
        $this->assertSame($expectedUpdated, $this->target->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->target->getUpdatedAt());
        $this->target->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());
    }
}
