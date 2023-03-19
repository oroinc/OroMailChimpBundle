<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Template;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TemplateTest extends \PHPUnit\Framework\TestCase
{
    private Template $target;

    protected function setUp(): void
    {
        $this->target = new Template();
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
     * @dataProvider boolSettersAndGettersDataProvider
     */
    public function testBoolSettersAndGetters(string $property, mixed $value)
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
            ['type', Template::TYPE_USER],
            ['name', 'String'],
            ['layout', 'Text'],
            ['layout', null],
            ['category', 'String'],
            ['category', null],
            ['previewImage', 'Text'],
            ['previewImage', null],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];
    }

    public function boolSettersAndGettersDataProvider(): array
    {
        return [
            ['active', true],
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
        $this->assertGreaterThanOrEqual($expectedUpdated, $this->target->getUpdatedAt());
        $this->assertNotSame($expectedUpdated, $this->target->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $this->assertNull($this->target->getUpdatedAt());
        $this->target->preUpdate();
        $this->assertInstanceOf(\DateTime::class, $this->target->getUpdatedAt());
    }
}
