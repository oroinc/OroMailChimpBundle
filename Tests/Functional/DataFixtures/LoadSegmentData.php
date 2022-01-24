<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ContactBundle\Entity\Contact;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkCRMBundle\Entity\TestCustomerWithContactInformation;
use Oro\Bundle\UserBundle\Entity\User;

class LoadSegmentData extends AbstractMailChimpFixture
{
    /**
     * @var array Channels configuration
     */
    protected $data = [
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
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $manager->getRepository(User::class)->findOneByUsername('admin');
        $organization = $manager->getRepository(Organization::class)->getFirst();

        foreach ($this->data as $data) {
            $entity = new Segment();
            $type = $manager
                ->getRepository(SegmentType::class)
                ->find($data['type']);
            $entity->setType($type);
            $entity->setDefinition(json_encode($data['definition']));
            $entity->setOrganization($organization);
            $entity->setOwner($user->getBusinessUnits()->first());

            $this->setEntityPropertyValues($entity, $data, ['reference', 'type', 'definition']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }
}
