<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Repository\SubscribersListRepository;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarFieldsInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Mailchimp subscribers list entity class.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *\
 */
#[ORM\Entity(repositoryClass: SubscribersListRepository::class)]
#[ORM\Table(name: 'orocrm_mc_subscribers_list')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing'],
        'entity' => ['icon' => 'fa-users']
    ]
)]
class SubscribersList implements OriginAwareInterface
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'origin_id', type: Types::STRING, length: 32, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $originId = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    /**
     * @var int
     */
    #[ORM\Column(name: 'web_id', type: Types::BIGINT, nullable: false)]
    protected $webId;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $name = null;

    #[ORM\Column(name: 'email_type_option', type: Types::BOOLEAN)]
    protected ?bool $emailTypeOption = null;

    #[ORM\Column(name: 'use_awesomebar', type: Types::BOOLEAN)]
    protected ?bool $useAwesomeBar = null;

    #[ORM\Column(name: 'default_from_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $defaultFromName = null;

    #[ORM\Column(name: 'default_from_email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $defaultFromEmail = null;

    #[ORM\Column(name: 'default_subject', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $defaultSubject = null;

    #[ORM\Column(name: 'default_language', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $defaultLanguage = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'list_rating', type: Types::FLOAT, nullable: true)]
    protected $listRating;

    #[ORM\Column(name: 'subscribe_url_short', type: Types::TEXT, nullable: true)]
    protected ?string $subscribeUrlShort = null;

    #[ORM\Column(name: 'subscribe_url_long', type: Types::TEXT, nullable: true)]
    protected ?string $subscribeUrlLong = null;

    #[ORM\Column(name: 'beamer_address', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $beamerAddress = null;

    #[ORM\Column(name: 'visibility', type: Types::TEXT, nullable: true)]
    protected ?string $visibility = null;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'member_count', type: Types::FLOAT, nullable: true)]
    protected $memberCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'unsubscribe_count', type: Types::FLOAT, nullable: true)]
    protected $unsubscribeCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'cleaned_count', type: Types::FLOAT, nullable: true)]
    protected $cleanedCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'member_count_since_send', type: Types::FLOAT, nullable: true)]
    protected $memberCountSinceSend;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'unsubscribe_count_since_send', type: Types::FLOAT, nullable: true)]
    protected $unsubscribeCountSinceSend;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'cleaned_count_since_send', type: Types::FLOAT, nullable: true)]
    protected $cleanedCountSinceSend;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'campaign_count', type: Types::FLOAT, nullable: true)]
    protected $campaignCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'grouping_count', type: Types::FLOAT, nullable: true)]
    protected $groupingCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'group_count', type: Types::FLOAT, nullable: true)]
    protected $groupCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'merge_var_count', type: Types::FLOAT, nullable: true)]
    protected $mergeVarCount;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'avg_sub_rate', type: Types::FLOAT, nullable: true)]
    protected $avgSubRate;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'avg_unsub_rate', type: Types::FLOAT, nullable: true)]
    protected $avgUsubRate;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'target_sub_rate', type: Types::FLOAT, nullable: true)]
    protected $targetSubRate;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'open_rate', type: Types::FLOAT, nullable: true)]
    protected $openRate;

    /**
     * @return float|null
     */
    #[ORM\Column(name: 'click_rate', type: Types::FLOAT, nullable: true)]
    protected $clickRate;

    /**
     * @var MergeVarFieldsInterface|null
     */
    protected $mergeVarFields;

    /**
     * @var array
     */
    #[ORM\Column(name: 'merge_var_config', type: 'json_array', nullable: true)]
    protected $mergeVarConfig;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param string $originId
     * @return SubscribersList
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;

        return $this;
    }

    /**
     * @return Channel
     */
    #[\Override]
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     * @return SubscribersList
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $owner
     * @return SubscribersList
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return int
     */
    public function getWebId()
    {
        return $this->webId;
    }

    /**
     * @param int $webId
     * @return SubscribersList
     */
    public function setWebId($webId)
    {
        $this->webId = $webId;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return SubscribersList
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isEmailTypeOption()
    {
        return $this->emailTypeOption;
    }

    /**
     * @param boolean $emailTypeOption
     * @return SubscribersList
     */
    public function setEmailTypeOption($emailTypeOption)
    {
        $this->emailTypeOption = $emailTypeOption;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUseAwesomeBar()
    {
        return $this->useAwesomeBar;
    }

    /**
     * @param boolean $useAwesomeBar
     * @return SubscribersList
     */
    public function setUseAwesomeBar($useAwesomeBar)
    {
        $this->useAwesomeBar = $useAwesomeBar;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultFromName()
    {
        return $this->defaultFromName;
    }

    /**
     * @param string $defaultFromName
     * @return SubscribersList
     */
    public function setDefaultFromName($defaultFromName)
    {
        $this->defaultFromName = $defaultFromName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultFromEmail()
    {
        return $this->defaultFromEmail;
    }

    /**
     * @param string $defaultFromEmail
     * @return SubscribersList
     */
    public function setDefaultFromEmail($defaultFromEmail)
    {
        $this->defaultFromEmail = $defaultFromEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultSubject()
    {
        return $this->defaultSubject;
    }

    /**
     * @param string $defaultSubject
     * @return SubscribersList
     */
    public function setDefaultSubject($defaultSubject)
    {
        $this->defaultSubject = $defaultSubject;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultLanguage()
    {
        return $this->defaultLanguage;
    }

    /**
     * @param string $defaultLanguage
     * @return SubscribersList
     */
    public function setDefaultLanguage($defaultLanguage)
    {
        $this->defaultLanguage = $defaultLanguage;
        return $this;
    }

    /**
     * @return float
     */
    public function getListRating()
    {
        return $this->listRating;
    }

    /**
     * @param float $listRating
     * @return SubscribersList
     */
    public function setListRating($listRating)
    {
        $this->listRating = $listRating;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubscribeUrlShort()
    {
        return $this->subscribeUrlShort;
    }

    /**
     * @param string $subscribeUrlShort
     * @return SubscribersList
     */
    public function setSubscribeUrlShort($subscribeUrlShort)
    {
        $this->subscribeUrlShort = $subscribeUrlShort;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubscribeUrlLong()
    {
        return $this->subscribeUrlLong;
    }

    /**
     * @param string $subscribeUrlLong
     * @return SubscribersList
     */
    public function setSubscribeUrlLong($subscribeUrlLong)
    {
        $this->subscribeUrlLong = $subscribeUrlLong;
        return $this;
    }

    /**
     * @return string
     */
    public function getBeamerAddress()
    {
        return $this->beamerAddress;
    }

    /**
     * @param string $beamerAddress
     * @return SubscribersList
     */
    public function setBeamerAddress($beamerAddress)
    {
        $this->beamerAddress = $beamerAddress;
        return $this;
    }

    /**
     * @return string
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @param string $visibility
     * @return SubscribersList
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * @return float
     */
    public function getMemberCount()
    {
        return $this->memberCount;
    }

    /**
     * @param float $memberCount
     * @return SubscribersList
     */
    public function setMemberCount($memberCount)
    {
        $this->memberCount = $memberCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getUnsubscribeCount()
    {
        return $this->unsubscribeCount;
    }

    /**
     * @param float $unsubscribeCount
     * @return SubscribersList
     */
    public function setUnsubscribeCount($unsubscribeCount)
    {
        $this->unsubscribeCount = $unsubscribeCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getCleanedCount()
    {
        return $this->cleanedCount;
    }

    /**
     * @param float $cleanedCount
     * @return SubscribersList
     */
    public function setCleanedCount($cleanedCount)
    {
        $this->cleanedCount = $cleanedCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getMemberCountSinceSend()
    {
        return $this->memberCountSinceSend;
    }

    /**
     * @param float $memberCountSinceSend
     * @return SubscribersList
     */
    public function setMemberCountSinceSend($memberCountSinceSend)
    {
        $this->memberCountSinceSend = $memberCountSinceSend;
        return $this;
    }

    /**
     * @return float
     */
    public function getUnsubscribeCountSinceSend()
    {
        return $this->unsubscribeCountSinceSend;
    }

    /**
     * @param float $unsubscribeCountSinceSend
     * @return SubscribersList
     */
    public function setUnsubscribeCountSinceSend($unsubscribeCountSinceSend)
    {
        $this->unsubscribeCountSinceSend = $unsubscribeCountSinceSend;
        return $this;
    }

    /**
     * @return float
     */
    public function getCleanedCountSinceSend()
    {
        return $this->cleanedCountSinceSend;
    }

    /**
     * @param float $cleanedCountSinceSend
     * @return SubscribersList
     */
    public function setCleanedCountSinceSend($cleanedCountSinceSend)
    {
        $this->cleanedCountSinceSend = $cleanedCountSinceSend;
        return $this;
    }

    /**
     * @return float
     */
    public function getCampaignCount()
    {
        return $this->campaignCount;
    }

    /**
     * @param float $campaignCount
     * @return SubscribersList
     */
    public function setCampaignCount($campaignCount)
    {
        $this->campaignCount = $campaignCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getGroupingCount()
    {
        return $this->groupingCount;
    }

    /**
     * @param float $groupingCount
     * @return SubscribersList
     */
    public function setGroupingCount($groupingCount)
    {
        $this->groupingCount = $groupingCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getGroupCount()
    {
        return $this->groupCount;
    }

    /**
     * @param float $groupCount
     * @return SubscribersList
     */
    public function setGroupCount($groupCount)
    {
        $this->groupCount = $groupCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getMergeVarCount()
    {
        return $this->mergeVarCount;
    }

    /**
     * @param float $mergeVarCount
     * @return SubscribersList
     */
    public function setMergeVarCount($mergeVarCount)
    {
        $this->mergeVarCount = $mergeVarCount;
        return $this;
    }

    /**
     * @return float
     */
    public function getAvgSubRate()
    {
        return $this->avgSubRate;
    }

    /**
     * @param float $avgSubRate
     * @return SubscribersList
     */
    public function setAvgSubRate($avgSubRate)
    {
        $this->avgSubRate = $avgSubRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getAvgUsubRate()
    {
        return $this->avgUsubRate;
    }

    /**
     * @param float $avgUsubRate
     * @return SubscribersList
     */
    public function setAvgUsubRate($avgUsubRate)
    {
        $this->avgUsubRate = $avgUsubRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getTargetSubRate()
    {
        return $this->targetSubRate;
    }

    /**
     * @param float $targetSubRate
     * @return SubscribersList
     */
    public function setTargetSubRate($targetSubRate)
    {
        $this->targetSubRate = $targetSubRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getOpenRate()
    {
        return $this->openRate;
    }

    /**
     * @param float $openRate
     * @return SubscribersList
     */
    public function setOpenRate($openRate)
    {
        $this->openRate = $openRate;
        return $this;
    }

    /**
     * @return float
     */
    public function getClickRate()
    {
        return $this->clickRate;
    }

    /**
     * @param float $clickRate
     * @return SubscribersList
     */
    public function setClickRate($clickRate)
    {
        $this->clickRate = $clickRate;
        return $this;
    }

    /**
     * @return MergeVarFieldsInterface|null
     */
    public function getMergeVarFields()
    {
        return $this->mergeVarFields;
    }

    /**
     * @param MergeVarFieldsInterface|null $mergeVarFields|null MergeVarFieldsInterface
     * @return SubscribersList
     */
    public function setMergeVarFields(?MergeVarFieldsInterface $mergeVarFields = null)
    {
        $this->mergeVarFields = $mergeVarFields;

        return $this;
    }

    /**
     * @return array
     */
    public function getMergeVarConfig()
    {
        return (array)$this->mergeVarConfig;
    }

    /**
     * @param array|null $data
     * @return SubscribersList
     */
    public function setMergeVarConfig(?array $data = null)
    {
        $this->mergeVarFields = null;
        $this->mergeVarConfig = $data;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     * @return SubscribersList
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return SubscribersList
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        if (!$this->updatedAt) {
            $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }
}
