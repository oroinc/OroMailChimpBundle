<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings;
use Oro\Bundle\MailChimpBundle\Entity\Template;
use Symfony\Component\HttpFoundation\ParameterBag;

class MailChimpTransportSettingsTest extends \PHPUnit\Framework\TestCase
{
    private MailChimpTransportSettings $target;

    protected function setUp(): void
    {
        $this->target = new MailChimpTransportSettings();
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
            ['template', $this->createMock(Template::class)],
        ];
    }

    public function testReceiveActivities()
    {
        $this->assertTrue($this->target->isReceiveActivities());
        $this->target->setReceiveActivities(false);
        $this->assertFalse($this->target->isReceiveActivities());
    }

    public function testSettingsBag()
    {
        $channel = $this->createMock(Channel::class);
        $template = new Template();
        $this->target->setChannel($channel);
        $this->target->setTemplate($template);
        $this->target->setReceiveActivities(true);
        $this->assertNotNull($this->target->getChannel());
        $this->assertNotNull($this->target->getTemplate());

        $expectedSettings = [
            'channel' => $channel,
            'receiveActivities' => true
        ];
        $this->assertEquals(
            new ParameterBag($expectedSettings),
            $this->target->getSettingsBag()
        );
    }
}
