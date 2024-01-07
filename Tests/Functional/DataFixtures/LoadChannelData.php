<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadChannelData extends AbstractMailChimpFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    private array $channelData = [
        [
            'name' => 'mailchimp1',
            'type' => 'mailchimp',
            'transport' => 'mailchimp:transport_one',
            'connectors' => ['list', 'campaign', 'static_segment', 'member', 'member_activity'],
            'enabled' => true,
            'reference' => 'mailchimp:channel_1',
            'synchronizationSettings' => [
                'isTwoWaySyncEnabled' => true
            ],
        ],
        [
            'name' => 'mailchimp2',
            'type' => 'mailchimp',
            'transport' => 'mailchimp:transport_two',
            'connectors' => ['list'],
            'enabled' => true,
            'reference' => 'mailchimp_transport:channel_2',
            'synchronizationSettings' => [
                'isTwoWaySyncEnabled' => true
            ],
        ],
        [
            'name' => 'disabledMailchimp1',
            'type' => 'mailchimp',
            'transport' => 'mailchimp:transport_three',
            'connectors' => ['list'],
            'enabled' => false,
            'reference' => 'mailchimp_transport:channel_disabled_1',
            'synchronizationSettings' => [
                'isTwoWaySyncEnabled' => true
            ],
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadTransportData::class, LoadOrganization::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $userManager = $this->container->get('oro_user.manager');
        $admin = $this->getReference(LoadUser::USER);
        $inactiveUser = $this->loadInactiveUser($userManager);
        foreach ($this->channelData as $data) {
            $entity = new Channel();
            $data['transport'] = $this->getReference($data['transport']);
            $entity->setDefaultUserOwner($data['enabled'] ? $admin : $inactiveUser);
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $this->setEntityPropertyValues($entity, $data, ['reference', 'synchronizationSettings']);
            $this->setReference($data['reference'], $entity);
            if (isset($data['synchronizationSettings'])) {
                foreach ($data['synchronizationSettings'] as $key => $value) {
                    $entity->getSynchronizationSettingsReference()->offsetSet($key, $value);
                }
            }
            $manager->persist($entity);
        }
        $manager->flush();
    }

    private function loadInactiveUser(UserManager $userManager): User
    {
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername(uniqid('inactive.'));
        $user->setEmail('inactive@example.com');
        $user->setPassword('the_password');
        $user->setEnabled(false);

        $userManager->updateUser($user);

        return $user;
    }
}
