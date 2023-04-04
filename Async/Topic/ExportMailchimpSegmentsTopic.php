<?php

namespace Oro\Bundle\MailChimpBundle\Async\Topic;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Topic\AbstractTopic;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A topic to export Mailchimp segments
 */
class ExportMailchimpSegmentsTopic extends AbstractTopic implements JobAwareTopicInterface
{
    public const NAME = 'oro.mailchimp.export_mailchimp_segments';

    private DoctrineHelper $doctrineHelper;

    public function __construct(
        DoctrineHelper $doctrineHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
    }

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
        $this->configureMessageBodyIncludeIntegration($resolver);
        $this->configureMessageBodyIncludeUser($resolver);

        $resolver
            ->setRequired('segmentsIds')
            ->setAllowedTypes('segmentsIds', 'int[]');
    }

    private function configureMessageBodyIncludeIntegration(OptionsResolver $resolver): void
    {
        $resolver->define('integrationId')
            ->required()
            ->allowedTypes('int')
            ->normalize(function (Options $options, $value): Integration {
                /** @var Integration $entity */
                $entity = $this->getRepository(Integration::class)->find($value);

                if (!$entity) {
                    throw new InvalidOptionsException('The integration not found: '.$value);
                }

                if (!$entity->isEnabled()) {
                    throw new InvalidOptionsException('The integration is not enabled: '.$value);
                }

                return $entity;
            });
    }

    private function configureMessageBodyIncludeUser(OptionsResolver $resolver): void
    {
        $resolver->define('userId')
            ->default(null)
            ->allowedTypes('null', 'int')
            ->normalize(function (Options $options, $value): User {
                $entity = null;

                // if user id is null and only one target static segment
                // lets get user from static segment
                if (!$value && 1 === \count($segmentsIds= $options['segmentsIds'])) {
                    $segmentId = reset($segmentsIds);
                    /** @var StaticSegment $segment */
                    $segment = $this->getRepository(StaticSegment::class)->find($segmentId);
                    $entity = $segment->getMarketingList()->getOwner();
                }

                if ($value) {
                    /** @var User $entity */
                    $entity = $this->getRepository(User::class)->find($value);
                }

                if (!$entity) {
                    throw new InvalidOptionsException('The user not found: '.$value);
                }

                if (!$entity->isEnabled()) {
                    throw new InvalidOptionsException('The user is not enabled: '.$value);
                }

                return $entity;
            });
    }

    public function createJobName($messageBody): string
    {
        return 'oro_mailchimp:export_mailchimp:' . $messageBody['integrationId'];
    }

    private function getRepository(string $className): EntityRepository
    {
        return $this->doctrineHelper->getEntityRepository($className);
    }
}
