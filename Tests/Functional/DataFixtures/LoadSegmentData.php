<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\TestFrameworkCRMBundle\Entity\TestCustomerWithContactInformation;
use Oro\Bundle\UserBundle\Entity\User;

class LoadSegmentData extends AbstractMailChimpFixture implements DependentFixtureInterface
{
    protected array $data = [
        [
            'type' => 'dynamic',
            'name' => 'Test ML Segment',
            'description' => 'description',
            'entity' => Contact::class,
            'definition' => [
                'columns' => [
                    [
                        'name' => 'primaryEmail',
                        'label' => 'Primary Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                    [
                        'name' => 'firstName',
                        'label' => 'First Name',
                        'sorting' => '',
                        'func' => null,
                    ],
                    [
                        'name' => 'lastName',
                        'label' => 'Last Name',
                        'sorting' => '',
                        'func' => null,
                    ],
                ],
                'filters' => [
                    [
                        [
                            'columnName' => 'lastName',
                            'criterion' => [
                                'filter' => 'string',
                                'data' => [
                                    'value' => 'Case',
                                    'type' => '1'
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'columnName' => 'createdAt',
                            'criterion' => [
                                'filter' => 'datetime',
                                'data' => [
                                    'type' => '3',
                                    'part' => 'value',
                                    'value' => ['start' => '1935-01-01 00:00', 'end' => '']
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'reference' => 'mailchimp:ml_one:segment',
        ],
        [
            'type' => 'dynamic',
            'name' => 'Test ML Customer Segment',
            'description' => 'description',
            'entity' => TestCustomerWithContactInformation::class,
            'definition' => [
                'columns' => [
                    [
                        'name' => 'email',
                        'label' => 'Email',
                        'sorting' => '',
                        'func' => null,
                    ],
                    [
                        'name' => 'name',
                        'label' => 'Name',
                        'sorting' => '',
                        'func' => null,
                    ]
                ],
                'filters' => []
            ],
            'reference' => 'mailchimp:ml_two:segment',
        ],
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
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach ($this->data as $data) {
            $entity = new Segment();
            $entity->setType($manager->getRepository(SegmentType::class)->find($data['type']));
            $entity->setDefinition(json_encode($data['definition'], JSON_THROW_ON_ERROR));
            $entity->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
            $entity->setOwner($user->getBusinessUnits()->first());
            $this->setEntityPropertyValues($entity, $data, ['reference', 'type', 'definition']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
