<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class MemberTest extends \PHPUnit\Framework\TestCase
{
    private Member $target;

    #[\Override]
    protected function setUp(): void
    {
        $this->target = new Member();
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

    public function settersAndGettersDataProvider(): array
    {
        return [
            ['originId', 123456789],
            ['channel', $this->createMock(Channel::class)],
            ['email', 'email@example.com'],
            ['phone', '555-666-777'],
            ['status', Member::STATUS_CLEANED],
            ['firstName', 'John'],
            ['lastName', 'Doe'],
            ['memberRating', 2],
            ['optedInAt', new \DateTime()],
            ['optedInAt', null],
            ['optedInIpAddress', '5.6.7.8'],
            ['confirmedAt', new \DateTime()],
            ['confirmedIpAddress', null],
            ['latitude', '3910.57962'],
            ['longitude', '3910.57962'],
            ['gmtOffset', '3'],
            ['dstOffset', '3'],
            ['timezone', 'America/Los_Angeles'],
            ['cc', 'us'],
            ['region', 'ua'],
            ['lastChangedAt', new \DateTime()],
            ['lastChangedAt', null],
            ['euid', '123'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['updatedAt', null],
            ['subscribersList', $this->createMock(SubscribersList::class)],
            ['mergeVarValues', ['Email Address' => 'test@example.com']],
            ['owner', $this->createMock(Organization::class)],
        ];
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
