<?php

namespace Oro\Bundle\MailChimpBundle\Async\Topic;

use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to export Mailchimp segments
 */
class ExportMailchimpSegmentsTopic extends AbstractTopic
{
    public const NAME = 'oro.mailchimp.export_mailchimp_segments';

    public static function getName(): string
    {
        return self::NAME;
    }

    public static function getDescription(): string
    {
        return 'Exports Mailchimp segments.';
    }

    public function getDefaultPriority(string $queueName): string
    {
        return MessagePriority::VERY_LOW;
    }

    public function configureMessageBody(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('integrationId')
            ->setAllowedTypes('integrationId', 'int');

        $resolver
            ->setRequired('segmentsIds')
            ->setAllowedTypes('segmentsIds', 'int[]');
    }
}
