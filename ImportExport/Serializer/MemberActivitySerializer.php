<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Serializer;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\MemberActivity;
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;

/**
 * Added during performance improvement. Please, keep it as simple as possible.
 * Used for batch importing of member activities from MailChimp, may process significant amount of records.
 */
class MemberActivitySerializer implements ContextAwareDenormalizerInterface
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
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     */
    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = [])
    {
        $result = new MemberActivity();
        // Scalar fields
        if (array_key_exists('email', $data)) {
            $result->setEmail($data['email']);
        }
        if (array_key_exists('action', $data)) {
            $result->setAction($data['action']);
        }
        if (array_key_exists('ip', $data)) {
            $result->setIp($data['ip']);
        }
        if (array_key_exists('url', $data)) {
            $result->setUrl($data['url']);
        }

        // DateTime fields
        if (!empty($data['activityTime'])) {
            $result->setActivityTime($this->getDateTime($data['activityTime'], $context));
        }

        // Relations
        /** @var Channel $channel */
        $channel = $this->doctrineHelper->getEntityReference($this->channelEntity, $context['channel']);
        $result->setChannel($channel);

        if (array_key_exists('campaign', $data)) {
            $result->setCampaign($data['campaign']);
            $result->getCampaign()->setChannel($channel);
        }

        if (array_key_exists('member', $data)) {
            $member = new Member();
            if (!empty($data['member']['originId'])) {
                $member->setOriginId($data['member']['originId']);
            }
            if (!empty($data['member']['email'])) {
                $member->setEmail($data['member']['email']);
            }
            $member->setChannel($channel);
            $result->setMember($member);
        }

        return $result;
    }

    protected function getDateTime(string $dateString, array $context = []): ?\DateTime
    {
        return $this->dateTimeSerializer->denormalize($dateString, 'DateTime', 'datetime', $context);
    }

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, MemberActivity::class, true)
            && is_array($data)
            && !empty($context['channel']);
    }
}
