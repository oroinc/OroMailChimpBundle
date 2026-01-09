<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Async\Topic;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ExportMailchimpSegmentsTopicTest extends AbstractTopicTestCase
{
    private const NOT_FOUND_ID = -1;
    private const DISABLED_ID = -2;

    #[\Override]
    protected function getTopic(): TopicInterface
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $integrationRepository = $this->createMock(EntityRepository::class);
        $integrationRepository
            ->expects(self::any())
            ->method('find')
            ->willReturnCallback([$this, 'createIntegration']);

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository
            ->expects(self::any())
            ->method('find')
            ->willReturnCallback([$this, 'createUser']);

        $staticSegmentRepository = $this->createMock(EntityRepository::class);
        $staticSegmentRepository
            ->expects(self::any())
            ->method('find')
            ->willReturnCallback(function (int $segmentId) {
                return $this->createStaticSegment($segmentId, $segmentId !== 99);
            });

        $map = [
            User::class => $userRepository,
            Integration::class => $integrationRepository,
            StaticSegment::class => $staticSegmentRepository,
        ];

        $doctrineHelper
            ->expects(self::any())
            ->method('getEntityRepository')
            ->willReturnCallback(function ($className) use ($map) {
                return $map[$className];
            });

        return new ExportMailchimpSegmentsTopic(
            $doctrineHelper,
        );
    }

    public function createUser(int $id): ?User
    {
        if (self::NOT_FOUND_ID === $id) {
            return null;
        }

        $user = new User();
        $user->setId($id);
        $user->setSalt('the_salt');

        if (self::DISABLED_ID === $id) {
            $user->setEnabled(false);
        }

        return $user;
    }

    public function createIntegration(int $id): ?Integration
    {
        if (self::NOT_FOUND_ID === $id) {
            return null;
        }

        $integration = new Integration();
        ReflectionUtil::setId($integration, $id);

        if (self::DISABLED_ID === $id) {
            $integration->setEnabled(false);
        }

        return $integration;
    }

    public function createStaticSegment(int $id, bool $withMarketingList = true): StaticSegment
    {
        $staticSegment = new StaticSegment();
        $marketingList = new MarketingList();
        $marketingList->setOwner($this->createUser($id + 5));
        if ($withMarketingList) {
            $staticSegment->setMarketingList($marketingList);
        }

        return $staticSegment;
    }

    #[\Override]
    public function validBodyDataProvider(): array
    {
        $body = [
            'integrationId' => 1,
            'segmentsIds' => [1, 2, 3],
            'userId' => 4,
        ];

        $expectedBody = [
            'integrationId' => $this->createIntegration($body['integrationId']),
            'segmentsIds' => $body['segmentsIds'],
            'userId' => $this->createUser($body['userId']),
        ];

        return [
            'only required options' => [
                'body' => $body,
                'expectedBody' => $expectedBody,
            ],
            'userId extracted from a single segment' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [2]
                ],
                'expectedBody' => [
                    'integrationId' => $this->createIntegration($body['integrationId']),
                    'segmentsIds' => [2],
                    'userId' => $this->createUser(2 + 5),
                ],
            ],
        ];
    }

    #[\Override]
    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "integrationId", "segmentsIds" are missing./',
            ],
            'wrong userId type' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [1, 2, 3],
                    'userId' => '4',
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "userId" with value "4" is expected to be of type "null" or "int",' .
                    ' but is of type "string"./',
            ],
            'user not found by userId' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [1, 2, 3],
                    'userId' => self::NOT_FOUND_ID,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf('/The user not found./'),
            ],
            'user disabled by userId' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [1, 2, 3],
                    'userId' => self::DISABLED_ID,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf('/The user is not enabled: %s/', self::DISABLED_ID),
            ],
            'wrong integrationId type' => [
                'body' => [
                    'integrationId' => '1',
                    'segmentsIds' => [1, 2, 3],
                    'userId' => 4,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "integrationId" with value "1" is expected to be of type "int"/',
            ],
            'integration not found by integrationId' => [
                'body' => [
                    'integrationId' => self::NOT_FOUND_ID,
                    'segmentsIds' => [1, 2, 3],
                    'userId' => 4,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf('/The integration not found: %s/', self::NOT_FOUND_ID),
            ],
            'integration disabled by integrationId' => [
                'body' => [
                    'integrationId' => self::DISABLED_ID,
                    'segmentsIds' => [1, 2, 3],
                    'userId' => 4,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf('/The integration is not enabled: %s/', self::DISABLED_ID),
            ],
            'wrong segmentsIds type' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => 1,
                    'userId' => 2,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "segmentsIds" with value 1 is expected to be of type "int\[\]"/',
            ],
            'wrong segmentsIds array values type' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [
                        '1',
                    ],
                    'userId' => 2,
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "segmentsIds" with value array is expected to be'
                    . ' of type "int\[\]", but one of the elements is of type "string"./',
            ],
            'segment without marketing list' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => [99],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => sprintf('/The user not found./'),
            ],
        ];
    }

    public function testDefaultPriority(): void
    {
        self::assertEquals(MessagePriority::VERY_LOW, $this->getTopic()->getDefaultPriority('queueName'));
    }

    public function testCreateJobName(): void
    {
        $message = ['integrationId' => 42, 'userId' => 43, 'segmentsIds' => [2, 1]];

        self::assertSame(
            'oro_mailchimp:export_mailchimp:42:' . md5(json_encode([1, 2])),
            $this->getTopic()->createJobName($message)
        );
    }
}
