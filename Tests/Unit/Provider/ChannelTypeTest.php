<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;
use Oro\Bundle\MailChimpBundle\Provider\ChannelType;

class ChannelTypeTest extends \PHPUnit\Framework\TestCase
{
    private ChannelType $channel;

    #[\Override]
    protected function setUp(): void
    {
        $this->channel = new ChannelType();
    }

    public function testGetLabel()
    {
        $this->assertInstanceOf(ChannelInterface::class, $this->channel);
        $this->assertEquals('oro.mailchimp.channel_type.label', $this->channel->getLabel());
    }

    public function testGetIcon()
    {
        $this->assertInstanceOf(IconAwareIntegrationInterface::class, $this->channel);
        $this->assertEquals('bundles/oromailchimp/img/freddie.ico', $this->channel->getIcon());
    }
}
