<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings;
use Oro\Bundle\MailChimpBundle\Transport\MailChimpTransport;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadCampaignData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'originId' => 'campaign1',
            'webId' => '111',
            'status' => Campaign::STATUS_SENT,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_one',
        ],
        [
            'originId' => 'campaign2',
            'webId' => '112',
            'status' => Campaign::STATUS_SENT,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_2',
        ],
        [
            'originId' => 'campaign3',
            'webId' => '113',
            'status' => Campaign::STATUS_SENT,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_3',
        ],
        [
            'originId' => 'campaign4',
            'webId' => '114',
            'status' => Campaign::STATUS_SENT,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_4',
        ],
        [
            'originId' => 'campaign5',
            'webId' => '115',
            'status' => Campaign::STATUS_SENT,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_5',
        ],
        [
            'originId' => 'campaign6',
            'webID' => '116',
            'status' => Campaign::STATUS_SCHEDULE,
            'title' => 'Test Campaign Title',
            'subject' => 'Test Campaign',
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:campaign_6',
        ],

    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadStaticSegmentData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            /** @var Channel $channel */
            $channel = $this->getReference($data['channel']);

            $transportSettings = new MailChimpTransportSettings();
            $transportSettings->setChannel($channel);
            $transportSettings->setReceiveActivities(true);
            $manager->persist($transportSettings);

            $emailCampaign = new EmailCampaign();
            $emailCampaign->setSchedule(EmailCampaign::SCHEDULE_MANUAL);
            $emailCampaign->setName($data['subject']);
            $emailCampaign->setTransport(MailChimpTransport::NAME);
            $emailCampaign->setTransportSettings($transportSettings);
            $manager->persist($emailCampaign);

            $entity = new Campaign();
            $entity->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setEmailCampaign($emailCampaign);
            $entity->setSendTime(new \DateTime('now', new \DateTimeZone('UTC')));
            $data['subscribersList'] = $this->getReference($data['subscribersList']);
            $data['channel'] = $channel;
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
