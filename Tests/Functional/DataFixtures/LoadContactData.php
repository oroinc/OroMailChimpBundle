<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactEmail;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class LoadContactData extends AbstractFixture
{
    private array $contactsData = [
        [
            'firstName' => "Daniel\tst",
            'lastName'  => 'Case <a href="https://www.goo.com/search?q=json&oq=json&aqs=chrome..69">Дж.[s`ón]</a>',
            'email'     => 'member1@example.com',
        ],
        [
            'firstName' => 'John',
            'lastName'  => 'Case',
            'email'     => 'member2@example.com',
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $user = $manager->getRepository(User::class)->findOneByUsername('admin');
        $organization = $manager->getRepository(Organization::class)->getFirst();

        foreach ($this->contactsData as $contactData) {
            $contact = new Contact();
            $contact->setOwner($user);
            $contact->setOrganization($organization);
            $contact->setFirstName($contactData['firstName']);
            $contact->setLastName($contactData['lastName']);
            $email = new ContactEmail();
            $email->setEmail($contactData['email']);
            $email->setPrimary(true);
            $contact->addEmail($email);

            $manager->persist($contact);
            $this->setReference('contact:' . $contactData['email'], $contact);
        }

        $manager->flush();
    }
}
