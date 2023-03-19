<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MailChimpBundle\Entity\MarketingListEmail;

class LoadMarketingListEmailData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    private array $segmentData = [
        [
            'marketingList' => 'mailchimp:ml_one',
            'email' => 'test@example.com',
            'state' => 'in_list',
            'reference' => 'mailchimp:ml_email_one'
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->segmentData as $data) {
            $entity = new MarketingListEmail();
            $data['marketingList'] = $this->getReference($data['marketingList']);
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
        return [LoadStaticSegmentData::class];
    }
}
