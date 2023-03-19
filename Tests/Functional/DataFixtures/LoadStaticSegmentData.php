<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadStaticSegmentData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    protected array $segmentData = [
        [
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'marketingList' => 'mailchimp:ml_one',
            'channel' => 'mailchimp:channel_1',
            'name' => 'Test',
            'sync_status' => '',
            'remote_remove' => '0',
            'reference' => 'mailchimp:segment_one',
        ],
        [
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'marketingList' => 'mailchimp:ml_two',
            'channel' => 'mailchimp:channel_1',
            'name' => 'Test',
            'sync_status' => '',
            'remote_remove' => '0',
            'reference' => 'mailchimp:segment_two',
        ],
        [
            'subscribersList' => 'mailchimp:subscribers_list_two',
            'marketingList' => 'mailchimp:ml_one',
            'channel' => 'mailchimp:channel_1',
            'name' => 'Test',
            'sync_status' => '',
            'remote_remove' => '0',
            'reference' => 'mailchimp:segment_three',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $manager->getRepository(Organization::class)
            ->getFirst();

        foreach ($this->segmentData as $data) {
            $entity = new StaticSegment();
            $entity->setOwner($organization);
            $data['marketingList'] = $this->getReference($data['marketingList']);
            $data['subscribersList'] = $this->getReference($data['subscribersList']);
            $data['channel'] = $this->getReference($data['channel']);
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadMarketingListData::class, LoadSubscribersListData::class];
    }
}
