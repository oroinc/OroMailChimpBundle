<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransport;

class LoadTransportData extends AbstractMailChimpFixture
{
    private array $transportData = [
        [
            'reference' => 'mailchimp:transport_one',
            'apiKey' => 'f9e179585f382c4def28653b1cbddba5-us9',
        ],
        [
            'reference' => 'mailchimp:transport_two',
            'apiKey' => 'f9e179585f382c4def28653b1cbddba5-us9',
        ],
        [
            'reference' => 'mailchimp:transport_three',
            'apiKey' => 'f9e179585f382c4def28653b1cbddba5-us9',
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->transportData as $data) {
            $entity = new MailChimpTransport();
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
