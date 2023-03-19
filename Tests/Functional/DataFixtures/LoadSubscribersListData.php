<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class LoadSubscribersListData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'channel' => 'mailchimp:channel_1',
            'originId' => '54321',
            'webId' => '12345',
            'name' => 'MC',
            'email_type_option' => '0',
            'merge_var_config' => [
                [
                    'name' => 'Email Address',
                    'req' => true,
                    'field_type' => 'email',
                    'public' => true,
                    'show' => true,
                    'order' => '1',
                    'default' => null,
                    'helptext' => null,
                    'size' => '25',
                    'tag' => 'EMAIL',
                    'id' => 0
                ],
                [
                    'name' => 'First Name',
                    'req' => false,
                    'field_type' => 'text',
                    'public' => true,
                    'show' => true,
                    'order' => '2',
                    'default' => '',
                    'helptext' => '',
                    'size' => '25',
                    'tag' => 'FNAME',
                    'id' => 1
                ],
                [
                    'name' => 'Last Name',
                    'req' => false,
                    'field_type' => 'text',
                    'public' => true,
                    'show' => true,
                    'order' => '3',
                    'default' => '',
                    'helptext' => '',
                    'size' => '25',
                    'tag' => 'LNAME',
                    'id' => 2
                ]
            ],
            'emailTypeOption' => true,
            'useAwesomebar' => true,
            'reference' => 'mailchimp:subscribers_list_one',
        ],
        [
            'channel' => 'mailchimp:channel_1',
            'originId' => '12345',
            'webId' => '54321',
            'name' => 'MC2',
            'email_type_option' => '0',
            'merge_var_config' => [],
            'emailTypeOption' => true,
            'useAwesomebar' => true,
            'reference' => 'mailchimp:subscribers_list_two',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();

        foreach ($this->data as $data) {
            $entity = new SubscribersList();
            $entity->setOwner($organization);
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
        return [LoadChannelData::class];
    }
}
