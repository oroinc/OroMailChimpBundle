<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\TestFrameworkCRMBundle\Entity\TestCustomerWithContactInformation;

class LoadMarketingListData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    protected array $mlData = [
        [
            'type' => 'dynamic',
            'name' => 'Test ML',
            'description' => '',
            'entity' => Contact::class,
            'reference' => 'mailchimp:ml_one',
            'segment' => 'mailchimp:ml_one:segment',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Customer',
            'description' => '',
            'entity' => TestCustomerWithContactInformation::class,
            'reference' => 'mailchimp:ml_two',
            'segment' => 'mailchimp:ml_two:segment',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadSegmentData::class,
            LoadContactData::class,
            LoadCustomerData::class,
            LoadUser::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->mlData as $data) {
            $entity = new MarketingList();
            $entity->setType($manager->getRepository(MarketingListType::class)->find($data['type']));
            $entity->setSegment($this->getReference($data['segment']));
            $entity->setOwner($this->getReference(LoadUser::USER));
            $this->setEntityPropertyValues($entity, $data, ['reference', 'type', 'segment']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
