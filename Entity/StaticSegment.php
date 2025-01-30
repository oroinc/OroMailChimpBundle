<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Mailchimp static segment entity class.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity(repositoryClass: StaticSegmentRepository::class)]
#[ORM\Table(name: 'orocrm_mc_static_segment')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-user'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class StaticSegment implements OriginAwareInterface
{
    /**#@+
     * @const string Status of Static Segment
     */
    const STATUS_NOT_SYNCED = 'not_synced';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SYNCED = 'synced';
    const STATUS_SYNC_FAILED = 'sync_failed';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SCHEDULED_BY_CHANGE = 'scheduled_by_change';
    const STATUS_IMPORTED = 'imported';
    /**#@-*/
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * @var integer|null
     */
    #[ORM\Column(name: 'origin_id', type: Types::BIGINT, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected $originId;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\ManyToOne(targetEntity: MarketingList::class)]
    #[ORM\JoinColumn(name: 'marketing_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?MarketingList $marketingList = null;

    #[ORM\ManyToOne(targetEntity: SubscribersList::class)]
    #[ORM\JoinColumn(name: 'subscribers_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?SubscribersList $subscribersList = null;

    /**
     * @var Collection<int, StaticSegmentMember>
     */
    #[ORM\OneToMany(mappedBy: 'staticSegment', targetEntity: StaticSegmentMember::class)]
    protected ?Collection $segmentMembers = null;

    /**
     * @var Collection<int, ExtendedMergeVar>
     */
    #[ORM\OneToMany(mappedBy: 'staticSegment', targetEntity: ExtendedMergeVar::class)]
    protected ?Collection $extendedMergeVars = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    #[ORM\Column(name: 'sync_status', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $syncStatus = null;

    #[ORM\Column(name: 'last_synced', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastSynced = null;

    #[ORM\Column(name: 'remote_remove', type: Types::BOOLEAN, nullable: false)]
    protected ?bool $remoteRemove = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'last_reset', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastReset = null;

    #[ORM\Column(name: 'member_count', type: Types::INTEGER, nullable: true)]
    protected ?int $memberCount = null;

    public function __construct()
    {
        $this->segmentMembers = new ArrayCollection();
        $this->extendedMergeVars = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return StaticSegment
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set syncStatus
     *
     * @param integer $syncStatus
     * @return StaticSegment
     */
    public function setSyncStatus($syncStatus)
    {
        $this->syncStatus = $syncStatus;

        return $this;
    }

    /**
     * Get syncStatus
     *
     * @return integer
     */
    public function getSyncStatus()
    {
        return $this->syncStatus;
    }

    /**
     * Set lastSynced
     *
     * @param DateTime $lastSynced
     * @return StaticSegment
     */
    public function setLastSynced($lastSynced)
    {
        $this->lastSynced = $lastSynced;

        return $this;
    }

    /**
     * Get lastSynced
     *
     * @return DateTime
     */
    public function getLastSynced()
    {
        return $this->lastSynced;
    }

    /**
     * Set remoteRemove
     *
     * @param boolean $remoteRemove
     * @return StaticSegment
     */
    public function setRemoteRemove($remoteRemove)
    {
        $this->remoteRemove = $remoteRemove;

        return $this;
    }

    /**
     * Get remoteRemove
     *
     * @return boolean
     */
    public function getRemoteRemove()
    {
        return $this->remoteRemove;
    }

    /**
     * @return integer
     */
    #[\Override]
    public function getOriginId()
    {
        return $this->originId;
    }

    /**
     * @param integer $originId
     * @return StaticSegment
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
     * @return StaticSegment
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set marketingList
     *
     * @param MarketingList|null $marketingList
     * @return StaticSegment
     */
    public function setMarketingList(?MarketingList $marketingList = null)
    {
        $this->marketingList = $marketingList;

        return $this;
    }

    /**
     * Get marketingList
     *
     * @return MarketingList
     */
    public function getMarketingList()
    {
        return $this->marketingList;
    }

    /**
     * Set subscribersList
     *
     * @param SubscribersList|null $subscribersList
     * @return StaticSegment
     */
    public function setSubscribersList(?SubscribersList $subscribersList = null)
    {
        $this->subscribersList = $subscribersList;

        return $this;
    }

    /**
     * Get subscribersList
     *
     * @return SubscribersList
     */
    public function getSubscribersList()
    {
        return $this->subscribersList;
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
     * @return StaticSegment
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return StaticSegment
     */
    public function setCreatedAt(DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime|null $updatedAt
     * @return StaticSegment
     */
    public function setUpdatedAt(?DateTime $updatedAt = null)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function prePersist()
    {
        if (!$this->createdAt) {
            $this->createdAt = new DateTime('now', new \DateTimeZone('UTC'));
        }

        if (!$this->updatedAt) {
            $this->updatedAt = new DateTime('now', new \DateTimeZone('UTC'));
        }
    }

    #[ORM\PreUpdate]
    public function preUpdate()
    {
        $this->updatedAt = new DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return DateTime
     */
    public function getLastReset()
    {
        return $this->lastReset;
    }

    /**
     * @param DateTime|null $lastReset
     *
     * @return StaticSegment
     */
    public function setLastReset(?DateTime $lastReset = null)
    {
        $this->lastReset = $lastReset;

        return $this;
    }

    /**
     * Set memberCount
     *
     * @param integer $memberCount
     * @return StaticSegment
     */
    public function setMemberCount($memberCount)
    {
        $this->memberCount = $memberCount;

        return $this;
    }

    /**
     * Get memberCount
     *
     * @return integer
     */
    public function getMemberCount()
    {
        return $this->memberCount;
    }

    /**
     * Add segmentMembers
     *
     * @param StaticSegmentMember $segmentMembers
     * @return StaticSegment
     */
    public function addSegmentMember(StaticSegmentMember $segmentMembers)
    {
        if (!$this->segmentMembers->contains($segmentMembers)) {
            $this->segmentMembers->add($segmentMembers);
        }

        return $this;
    }

    /**
     * Remove segmentMembers
     */
    public function removeSegmentMember(StaticSegmentMember $segmentMembers)
    {
        if ($this->segmentMembers->contains($segmentMembers)) {
            $this->segmentMembers->removeElement($segmentMembers);
        }
    }

    /**
     * Get segmentMembers
     *
     * @return Collection|StaticSegmentMember[]
     */
    public function getSegmentMembers()
    {
        return $this->segmentMembers;
    }

    /**
     * @param Collection $segmentMembers
     * @return StaticSegment
     */
    public function setSegmentMembers(Collection $segmentMembers)
    {
        $this->segmentMembers = $segmentMembers;

        return $this;
    }

    /**
     * @param ExtendedMergeVar $extendedMergeVar
     * @return StaticSegment
     */
    public function addExtendedMergeVar(ExtendedMergeVar $extendedMergeVar)
    {
        if (!$this->extendedMergeVars->contains($extendedMergeVar)) {
            $this->extendedMergeVars->add($extendedMergeVar);
        }

        return $this;
    }

    /**
     * @param ExtendedMergeVar $extendedMergeVar
     * @return StaticSegment
     */
    public function removeExtendedMergeVar(ExtendedMergeVar $extendedMergeVar)
    {
        if ($this->extendedMergeVars->contains($extendedMergeVar)) {
            $this->extendedMergeVars->removeElement($extendedMergeVar);
        }

        return $this;
    }

    /**
     * Retrieves extended merge vars.
     *
     * @param array $filterByStates
     * @return Collection|ExtendedMergeVar[]
     */
    public function getExtendedMergeVars(array $filterByStates = [])
    {
        if (!empty($filterByStates)) {
            return $this->extendedMergeVars->
            filter(function (ExtendedMergeVar $extendedMergeVar) use ($filterByStates) {
                return in_array($extendedMergeVar->getState(), $filterByStates, true);
            });
        }
        return $this->extendedMergeVars;
    }

    /**
     * @return Collection|ExtendedMergeVar[]
     */
    public function getSyncedExtendedMergeVars()
    {
        return $this->getExtendedMergeVars([ExtendedMergeVar::STATE_SYNCED]);
    }

    /**
     * @param Collection $collection
     * @return StaticSegment
     */
    public function setSyncedExtendedMergeVars(Collection $collection)
    {
        $this->extendedMergeVars = $collection;

        return $this;
    }
}
