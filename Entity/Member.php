<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\LastNameInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Mailchimp member entity class.
 *
 * @link http://apidocs.mailchimp.com/api/2.0/lists/member-info.php
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_mailchimp_member')]
#[ORM\Index(columns: ['email', 'subscribers_list_id'], name: 'mc_mmbr_email_list_idx')]
#[ORM\Index(columns: ['origin_id'], name: 'mc_mmbr_origin_idx')]
#[ORM\Index(columns: ['status'], name: 'mc_mmbr_status_idx')]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-user'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing'],
        'form' => ['grid_name' => 'orocrm-mailchimp-member-grid']
    ]
)]
class Member implements OriginAwareInterface, FirstNameInterface, LastNameInterface
{
    /**
     * @const Member is subscribed.
     */
    public const STATUS_SUBSCRIBED = 'subscribed';

    /**
     * @const Member is unsubscribed.
     */
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';

    /**
     * @const Member is cleaned.
     */
    public const STATUS_CLEANED = 'cleaned';

    /**
     * @const Member should be exported during next sync.
     */
    public const STATUS_EXPORT = 'export';

    /**
     * @const Sync failed for member. Such member will not be synced anymore.
     */
    public const STATUS_DROPPED = 'dropped';

    /**
     * @const Export failed for member. Such member will not be synced anymore.
     */
    public const STATUS_EXPORT_FAILED = 'export_failed';

    /**
     * @const Member's export data should be recollected and then status must be set to export
     */
    public const STATUS_UPDATE = 'update';

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var integer|null
     */
    #[ORM\Column(name: 'origin_id', type: Types::STRING, length: 32, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => false]])]
    protected $originId;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Channel $channel = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $email = null;

    #[ORM\Column(name: 'phone', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $phone = null;

    /**
     * The subscription status for this email address, either pending, subscribed, unsubscribed, or cleaned
     */
    #[ORM\Column(name: 'status', type: Types::STRING, length: 16, nullable: false)]
    protected ?string $status = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $firstName = null;

    #[ORM\Column(name: 'last_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $lastName = null;

    /**
     * The rating of the subscriber. This will be 1 - 5
     */
    #[ORM\Column(name: 'member_rating', type: Types::SMALLINT, nullable: true)]
    protected ?int $memberRating = null;

    /**
     * The date+time the opt-in completed.
     */
    #[ORM\Column(name: 'optedin_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $optedInAt = null;

    /**
     * IP Address this address opted in from.
     */
    #[ORM\Column(name: 'optedin_ip', type: Types::STRING, length: 20, nullable: true)]
    protected ?string $optedInIpAddress = null;

    /**
     * The date+time the confirm completed.
     */
    #[ORM\Column(name: 'confirmed_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $confirmedAt = null;

    /**
     * IP Address this address confirmed from.
     */
    #[ORM\Column(name: 'confirmed_ip', type: Types::STRING, length: 16, nullable: true)]
    protected ?string $confirmedIpAddress = null;

    #[ORM\Column(name: 'latitude', type: Types::STRING, length: 64, nullable: true)]
    protected ?string $latitude = null;

    #[ORM\Column(name: 'longitude', type: Types::STRING, length: 64, nullable: true)]
    protected ?string $longitude = null;

    #[ORM\Column(name: 'gmt_offset', type: Types::STRING, length: 16, nullable: true)]
    protected ?string $gmtOffset = null;

    /**
     * GMT offset during daylight savings (if DST not observered, will be same as gmtoff)
     */
    #[ORM\Column(name: 'dst_offset', type: Types::STRING, length: 16, nullable: true)]
    protected ?string $dstOffset = null;

    /**
     * The timezone we've place them in
     */
    #[ORM\Column(name: 'timezone', type: Types::STRING, length: 40, nullable: true)]
    protected ?string $timezone = null;

    /**
     * 2 digit ISO-3166 country code
     */
    #[ORM\Column(name: 'cc', type: Types::STRING, length: 2, nullable: true)]
    protected ?string $cc = null;

    /**
     * Generally state, province, or similar
     */
    #[ORM\Column(name: 'region', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $region = null;

    /**
     * The last time this record was changed. If the record is old enough, this may be blank.
     */
    #[ORM\Column(name: 'last_changed_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastChangedAt = null;

    /**
     * The unique id for an email address (not list related) - the email "id" returned from listMemberInfo,
     * Webhooks, Campaigns, etc.
     */
    #[ORM\Column(name: 'euid', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $euid = null;

    /**
     * Id used from the old v2 api. Also added on the export api v1.
     *
     * @var int|null
     */
    #[ORM\Column(name: 'leid', type: Types::BIGINT, nullable: true)]
    protected $leid;

    /**
     * @var array
     */
    #[ORM\Column(name: 'merge_var_values', type: 'json_array', nullable: true)]
    protected $mergeVarValues;

    #[ORM\ManyToOne(targetEntity: SubscribersList::class)]
    #[ORM\JoinColumn(name: 'subscribers_list_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?SubscribersList $subscribersList = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    /**
     * @var Collection<int, StaticSegmentMember>
     */
    #[ORM\OneToMany(mappedBy: 'member', targetEntity: StaticSegmentMember::class)]
    protected ?Collection $segmentMembers = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->segments = new ArrayCollection();
        $this->segmentMembers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return Member
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
     * @return Member
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param string $cc
     * @return Member
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getConfirmedAt()
    {
        return $this->confirmedAt;
    }

    /**
     * @param \DateTime|null $confirmedAt
     * @return Member
     */
    public function setConfirmedAt(?\DateTime $confirmedAt = null)
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfirmedIpAddress()
    {
        return $this->confirmedIpAddress;
    }

    /**
     * @param string $confirmedIpAddress
     * @return Member
     */
    public function setConfirmedIpAddress($confirmedIpAddress)
    {
        $this->confirmedIpAddress = $confirmedIpAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getDstOffset()
    {
        return $this->dstOffset;
    }

    /**
     * @param string $dstOffset
     * @return Member
     */
    public function setDstOffset($dstOffset)
    {
        $this->dstOffset = $dstOffset;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return Member
     */
    public function setEmail($email)
    {
        $this->email = $email;

        if (null === $this->originId) {
            $this->originId = md5(strtolower($email));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return Member
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getEuid()
    {
        return $this->euid;
    }

    /**
     * @param string $euid
     * @return Member
     */
    public function setEuid($euid)
    {
        $this->euid = $euid;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return Member
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getGmtOffset()
    {
        return $this->gmtOffset;
    }

    /**
     * @param string $gmtOffset
     * @return Member
     */
    public function setGmtOffset($gmtOffset)
    {
        $this->gmtOffset = $gmtOffset;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastChangedAt()
    {
        return $this->lastChangedAt;
    }

    /**
     * @param \DateTime|null $lastChangedAt
     * @return Member
     */
    public function setLastChangedAt(?\DateTime $lastChangedAt = null)
    {
        $this->lastChangedAt = $lastChangedAt;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return Member
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     * @return Member
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return int
     */
    public function getLeid()
    {
        return $this->leid;
    }

    /**
     * @param int $leid
     * @return Member
     */
    public function setLeid($leid)
    {
        $this->leid = $leid;

        return $this;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     * @return Member
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return int
     */
    public function getMemberRating()
    {
        return $this->memberRating;
    }

    /**
     * @param int $memberRating
     * @return Member
     */
    public function setMemberRating($memberRating)
    {
        $this->memberRating = $memberRating;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getOptedInAt()
    {
        return $this->optedInAt;
    }

    /**
     * @param \DateTime|null $optedInAt
     * @return Member
     */
    public function setOptedInAt(?\DateTime $optedInAt = null)
    {
        $this->optedInAt = $optedInAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getOptedInIpAddress()
    {
        return $this->optedInIpAddress;
    }

    /**
     * @param string $optedInIpAddress
     * @return Member
     */
    public function setOptedInIpAddress($optedInIpAddress)
    {
        $this->optedInIpAddress = $optedInIpAddress;

        return $this;
    }

    /**
     * @return string
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     * @return Member
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Member
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return Member
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * @return array
     */
    public function getMergeVarValues()
    {
        return $this->mergeVarValues;
    }

    /**
     * @param array|null $data
     * @return Member
     */
    public function setMergeVarValues(?array $data = null)
    {
        $this->mergeVarValues = $data;

        return $this;
    }

    /**
     * @return SubscribersList
     */
    public function getSubscribersList()
    {
        return $this->subscribersList;
    }

    /**
     * @param SubscribersList|null $subscribersList
     * @return Member
     */
    public function setSubscribersList(?SubscribersList $subscribersList = null)
    {
        $this->subscribersList = $subscribersList;

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
     * @return Member
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

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
     * @return Member
     */
    public function setCreatedAt(\DateTime $createdAt)
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
     * @param \DateTime|null $updatedAt
     * @return Member
     */
    public function setUpdatedAt(?\DateTime $updatedAt = null)
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

    /**
     * Add segmentMembers
     *
     * @param StaticSegmentMember $segmentMembers
     * @return Member
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
     * @return Collection
     */
    public function getSegmentMembers()
    {
        return $this->segmentMembers;
    }
}
