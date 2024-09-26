<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\ImportExport\DataConverter\MemberDataConverter;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

/**
 * Added during performance improvement. Please, keep it as simple as possible.
 * Used for batch importing of members from MailChimp, may process significant amount of records.
 */
class MemberImportSerializer implements ContextAwareDenormalizerInterface
{
    protected ?DateTimeSerializer $dateTimeSerializer = null;

    protected ?DoctrineHelper $doctrineHelper = null;

    protected ?string $channelEntity = null;

    public function setChannelEntity(string $channelEntity): self
    {
        $this->channelEntity = $channelEntity;

        return $this;
    }

    public function setDoctrineHelper(DoctrineHelper $doctrineHelper): self
    {
        $this->doctrineHelper = $doctrineHelper;

        return $this;
    }

    public function setDateTimeSerializer(DateTimeSerializer $dateTimeSerializer): self
    {
        $this->dateTimeSerializer = $dateTimeSerializer;

        return $this;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     *
     */
    #[\Override]
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        $result = new Member();
        // Scalar fields
        if (array_key_exists('originId', $data)) {
            $result->setOriginId($data['originId']);
        }
        // Email is a unique field that we use to verify the existence of a member. Unlike the origin_id field,
        // which is a md5 hash of the address (email is lowercase only).
        if (array_key_exists('email', $data)) {
            $result->setEmail($data['email']);
        }
        if (array_key_exists('status', $data)) {
            $result->setStatus($data['status']);
        }
        if (array_key_exists('memberRating', $data)) {
            $result->setMemberRating($data['memberRating']);
        }
        if (array_key_exists('optedInIpAddress', $data)) {
            $result->setOptedInIpAddress($data['optedInIpAddress']);
        }
        if (array_key_exists('confirmedIpAddress', $data)) {
            $result->setConfirmedIpAddress($data['confirmedIpAddress']);
        }
        if (array_key_exists('latitude', $data)) {
            $result->setLatitude($data['latitude']);
        }
        if (array_key_exists('longitude', $data)) {
            $result->setLongitude($data['longitude']);
        }
        if (array_key_exists('dstOffset', $data)) {
            $result->setDstOffset($data['dstOffset']);
        }
        if (array_key_exists('gmtOffset', $data)) {
            $result->setGmtOffset($data['gmtOffset']);
        }
        if (array_key_exists('timezone', $data)) {
            $result->setTimezone($data['timezone']);
        }
        if (array_key_exists('cc', $data)) {
            $result->setCc($data['cc']);
        }
        if (array_key_exists('region', $data)) {
            $result->setRegion($data['region']);
        }
        if (array_key_exists('euid', $data)) {
            $result->setEuid($data['euid']);
        }
        if (array_key_exists('mergeVarValues', $data)) {
            $result->setMergeVarValues($data['mergeVarValues']);
        }

        // DateTime fields
        if (array_key_exists('optedInAt', $data)) {
            $result->setOptedInAt($this->getDateTime($data['optedInAt'], $context));
        }
        if (!empty($data['confirmedAt'])) {
            $result->setConfirmedAt($this->getDateTime($data['confirmedAt'], $context));
        }
        if (!empty($data['lastChangedAt'])) {
            $result->setLastChangedAt($this->getDateTime($data['lastChangedAt'], $context));
        }

        // Relations
        /** @var Channel $channel */
        $channel = $this->doctrineHelper->getEntityReference($this->channelEntity, $context['channel']);
        $result->setChannel($channel);

        $subscribersList = null;
        if (!empty($data['subscribersList']['originId'])) {
            $subscribersList = new SubscribersList();
            $subscribersList->setChannel($channel);
            $subscribersList->setOriginId($data['subscribersList']['originId']);
        } elseif (!empty($data['subscribersList']['id'])) {
            $subscribersList = $this->doctrineHelper->getEntityReference(
                SubscribersList::class,
                $data['subscribersList']['id']
            );
        }

        if ($subscribersList) {
            $result->setSubscribersList($subscribersList);
        }

        return $result;
    }

    protected function getDateTime(string $dateString, array $context = []): ?\DateTime
    {
        return $this->dateTimeSerializer->denormalize($dateString, 'DateTime', 'datetime', $context);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        return is_array($data)
            && array_key_exists(MemberDataConverter::IMPORT_DATA, $data)
            && !empty($context['channel'])
            && is_a($type, Member::class, true);
    }
}
