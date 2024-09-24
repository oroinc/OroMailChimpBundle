<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkCRMBundle\Entity\TestCustomerWithContactInformation;

class LoadCustomerData extends AbstractFixture
{
    private array $data = [
        [
            'name' => 'customer1',
            'email' => 'customer1@example.com',
            'phone' => '+12345678900',
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            $customer = new TestCustomerWithContactInformation();

            $customer->setName($data['name']);
            $customer->setEmail($data['email']);
            $customer->setPhone($data['phone']);

            $manager->persist($customer);
            $this->setReference('mc:customer:' . $data['name'], $customer);
        }

        $manager->flush();
    }
}
