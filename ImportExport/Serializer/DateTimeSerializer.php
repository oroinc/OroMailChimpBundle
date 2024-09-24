<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Serializer;

use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DateTimeNormalizer as BaseNormalizer;
use Oro\Bundle\MailChimpBundle\Provider\ChannelType;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use Symfony\Component\Serializer\Exception\RuntimeException;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

/**
 * Date/time serializer.
 */
class DateTimeSerializer implements ContextAwareNormalizerInterface, ContextAwareDenormalizerInterface
{
    private const CHANNEL_TYPE_KEY = 'channelType';

    private BaseNormalizer $mailchimpNormalizer;

    private BaseNormalizer $isoNormalizer;

    public function __construct()
    {
        $this->mailchimpNormalizer = new BaseNormalizer(
            MailChimpTransport::DATETIME_FORMAT,
            MailChimpTransport::DATE_FORMAT,
            MailChimpTransport::TIME_FORMAT,
            MailChimpTransport::TIMEZONE
        );
        $this->isoNormalizer = new BaseNormalizer(\DateTime::ISO8601, 'Y-m-d', 'H:i:s', 'UTC');
    }

    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        try {
            return $this->mailchimpNormalizer->denormalize($data, $type, $format, $context);
        } catch (RuntimeException $e) {
            return $this->isoNormalizer->denormalize($data, $type, $format, $context);
        }
    }

    #[\Override]
    public function normalize($object, string $format = null, array $context = [])
    {
        return $this->mailchimpNormalizer->normalize($object, $format, $context);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return $this->mailchimpNormalizer->supportsDenormalization($data, $type, $format, $context)
            && !empty($context[self::CHANNEL_TYPE_KEY])
            && str_contains($context[self::CHANNEL_TYPE_KEY], ChannelType::TYPE);
    }

    #[\Override]
    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $this->mailchimpNormalizer->supportsNormalization($data, $format, $context)
            && !empty($context[self::CHANNEL_TYPE_KEY])
            && str_contains($context[self::CHANNEL_TYPE_KEY], ChannelType::TYPE);
    }
}
