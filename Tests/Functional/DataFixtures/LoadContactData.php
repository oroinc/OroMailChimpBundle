<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\ContactBundle\Entity\ContactEmail;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;

class LoadContactData extends AbstractFixture implements DependentFixtureInterface
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
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->contactsData as $contactData) {
            $contact = new Contact();
            $contact->setOwner($this->getReference(LoadUser::USER));
            $contact->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
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
