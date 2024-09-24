<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadMemberData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    protected array $data = [
        [
            'originId' => 210000000,
            'email' => 'member1@example.com',
            'status' => Member::STATUS_SUBSCRIBED,
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:member_one',
            'mergeVarValues' => ['EMAIL' => 'member1@example.com', 'FIRSTNAME' => 'Antonio', 'LASTNAME' => 'Banderas'],
        ],
        [
            'originId' => 210000001,
            'email' => 'member2@example.com',
            'status' => Member::STATUS_SUBSCRIBED,
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:member_two',
            'mergeVarValues' => ['EMAIL' => 'member2@example.com', 'FIRSTNAME' => 'Michael', 'LASTNAME' => 'Jackson'],
        ],
        [
            'originId' => 210000002,
            'email' => 'member3@example.com',
            'status' => Member::STATUS_SUBSCRIBED,
            'subscribersList' => 'mailchimp:subscribers_list_one',
            'channel' => 'mailchimp:channel_1',
            'reference' => 'mailchimp:member_three',
            'mergeVarValues' => ['EMAIL' => 'member3@example.com', 'FIRSTNAME' => null, 'LASTNAME' => null],
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadCampaignData::class, LoadOrganization::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $data) {
            $entity = new Member();
            $entity->setOwner($this->getReference(LoadOrganization::ORGANIZATION));
            $data['subscribersList'] = $this->getReference($data['subscribersList']);
            $data['channel'] = $this->getReference($data['channel']);
            $this->setEntityPropertyValues($entity, $data, ['reference']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
