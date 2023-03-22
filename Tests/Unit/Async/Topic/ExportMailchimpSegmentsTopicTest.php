<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Async\Topic;

use Oro\Bundle\MailChimpBundle\Async\Topic\ExportMailchimpSegmentsTopic;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Test\AbstractTopicTestCase;
use Oro\Component\MessageQueue\Topic\TopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class ExportMailchimpSegmentsTopicTest extends AbstractTopicTestCase
{
    protected function getTopic(): TopicInterface
    {
        return new ExportMailchimpSegmentsTopic();
    }

    public function validBodyDataProvider(): array
    {
        $requiredOptionsSet = [
            'integrationId' => 1,
            'segmentsIds' => [1, 2, 3],
        ];

        return [
            'only required options' => [
                'body' => $requiredOptionsSet,
                'expectedBody' => $requiredOptionsSet,
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            'empty' => [
                'body' => [],
                'exceptionClass' => MissingOptionsException::class,
                'exceptionMessage' =>
                    '/The required options "integrationId", "segmentsIds" are missing./',
            ],
            'wrong integrationId type' => [
                'body' => [
                    'integrationId' => '1',
                    'segmentsIds' => [1, 2, 3],
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "integrationId" with value "1" is expected to be of type "int"/',
            ],
            'wrong segmentsIds type' => [
                'body' => [
                    'integrationId' => 1,
                    'segmentsIds' => 1,
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
                ],
                'exceptionClass' => InvalidOptionsException::class,
                'exceptionMessage' => '/The option "segmentsIds" with value array is expected to be'
                    . ' of type "int\[\]", but one of the elements is of type "string"./',
            ],
        ];
    }

    public function testDefaultPriority(): void
    {
        self::assertEquals(MessagePriority::VERY_LOW, $this->getTopic()->getDefaultPriority('queueName'));
    }

    public function testCreateJobName(): void
    {
        self::assertSame(
            'oro_mailchimp:export_mailchimp:42',
            $this->getTopic()->createJobName(['integrationId' => 42])
        );
    }
}
