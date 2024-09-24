<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class MemberActivityTest extends \PHPUnit\Framework\TestCase
{
    private MemberActivity $target;

    #[\Override]
    protected function setUp(): void
    {
        $this->target = new MemberActivity();
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
            ['channel', $this->createMock(Channel::class)],
            ['campaign', $this->createMock(Campaign::class)],
            ['member', $this->createMock(Member::class)],
            ['owner', $this->createMock(Organization::class)],
            ['email', 'test@test.com'],
            ['action', 'open'],
            ['ip', '127.0.0.1'],
            ['url', 'http://test.com'],
            ['activityTime', new \DateTime()],
        ];
    }
}
