<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\Entity;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;
use Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures\LoadMarketingListEmailData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ChannelListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadMarketingListEmailData::class]);
    }

    public function testShouldRemoveRelatedMarketingListEmailsOnChannelRemoval()
    {
        $doctrine = self::getContainer()->get('doctrine');
        $channelManager = $doctrine->getManagerForClass(Channel::class);

        $channel = $this->getReference('mailchimp:channel_1');
        $channelManager->remove($channel);

        $emails = $doctrine->getRepository(MarketingListEmail::class)->findAll();

        self::assertEmpty($emails);
    }
}
