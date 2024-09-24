<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Repository\CampaignRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Mailchimp campaign entity class.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[ORM\Entity(repositoryClass: CampaignRepository::class)]
#[ORM\Table(name: 'orocrm_mailchimp_campaign')]
#[ORM\UniqueConstraint(name: 'mc_campaign_oid_cid_unq', columns: ['origin_id', 'channel_id'])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    defaultValues: [
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing'],
        'entity' => ['icon' => 'fa-envelope']
    ]
)]
class Campaign implements OriginAwareInterface
{
    const ACTIVITY_ENABLED = 'enabled';
    const ACTIVITY_DISABLED = 'disabled';
    const ACTIVITY_EXPIRED = 'expired';
    /**#@+
     * @const string Status of Campaign
     */
    const STATUS_SAVE = 'save';
    const STATUS_SENT = 'sent';
    const STATUS_SENDING = 'sending';
    const STATUS_PAUSED = 'paused';
    const STATUS_SCHEDULE = 'schedule';
    /**#@-*/

    /**#@+
     * @const string Type of Campaign
     */
    const TYPE_REGULAR = 'regular';
    const TYPE_PLAINTEXT = 'plaintext';
    const TYPE_ABSPLIT = 'absplit';
    const TYPE_RSS = 'rss';
    const TYPE_AUTO = 'automation';
    const TYPE_VAR = 'variate';

    /**#@-*/
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

    #[ORM\Column(name: 'title', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(name: 'subject', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'from_email', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fromEmail = null;

    #[ORM\Column(name: 'from_name', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $fromName = null;

    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Template $template = null;

    #[ORM\ManyToOne(targetEntity: StaticSegment::class)]
    #[ORM\JoinColumn(name: 'static_segment_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?StaticSegment $staticSegment = null;

    #[ORM\ManyToOne(targetEntity: SubscribersList::class)]
    #[ORM\JoinColumn(name: 'subscribers_list_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?SubscribersList $subscribersList = null;

    #[ORM\OneToOne(targetEntity: EmailCampaign::class)]
    #[ORM\JoinColumn(name: 'email_campaign_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?EmailCampaign $emailCampaign = null;

    #[ORM\Column(name: 'content_type', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $contentType = null;

    #[ORM\Column(name: 'type', type: Types::STRING, length: 50, nullable: true)]
    protected ?string $type = null;

    #[ORM\Column(name: 'status', type: Types::STRING, length: 16, nullable: false)]
    protected ?string $status = null;

    #[ORM\Column(name: 'send_time', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $sendTime = null;

    #[ORM\Column(name: 'last_open_date', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $lastOpenDate = null;

    #[ORM\Column(name: 'archive_url', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $archiveUrl = null;

    #[ORM\Column(name: 'archive_url_long', type: Types::TEXT, nullable: true)]
    protected ?string $archiveUrlLong = null;

    #[ORM\Column(name: 'emails_sent', type: Types::INTEGER, nullable: true)]
    protected ?int $emailsSent = null;

    #[ORM\Column(name: 'tests_sent', type: Types::INTEGER, nullable: true)]
    protected ?int $testsSent = null;

    #[ORM\Column(name: 'tests_remain', type: Types::INTEGER, nullable: true)]
    protected ?int $testsRemain = null;

    #[ORM\Column(name: 'syntax_errors', type: Types::INTEGER, nullable: true)]
    protected ?int $syntaxErrors = null;

    #[ORM\Column(name: 'hard_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $hardBounces = null;

    #[ORM\Column(name: 'soft_bounces', type: Types::INTEGER, nullable: true)]
    protected ?int $softBounces = null;

    #[ORM\Column(name: 'unsubscribes', type: Types::INTEGER, nullable: true)]
    protected ?int $unsubscribes = null;

    #[ORM\Column(name: 'abuse_reports', type: Types::INTEGER, nullable: true)]
    protected ?int $abuseReports = null;

    #[ORM\Column(name: 'forwards', type: Types::INTEGER, nullable: true)]
    protected ?int $forwards = null;

    #[ORM\Column(name: 'forwards_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $forwardsOpens = null;

    #[ORM\Column(name: 'opens', type: Types::INTEGER, nullable: true)]
    protected ?int $opens = null;

    #[ORM\Column(name: 'unique_opens', type: Types::INTEGER, nullable: true)]
    protected ?int $uniqueOpens = null;

    #[ORM\Column(name: 'clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $clicks = null;

    #[ORM\Column(name: 'unique_clicks', type: Types::INTEGER, nullable: true)]
    protected ?int $uniqueClicks = null;

    #[ORM\Column(name: 'users_who_clicked', type: Types::INTEGER, nullable: true)]
    protected ?int $usersWhoClicked = null;

    #[ORM\Column(name: 'unique_likes', type: Types::INTEGER, nullable: true)]
    protected ?int $uniqueLikes = null;

    #[ORM\Column(name: 'recipient_likes', type: Types::INTEGER, nullable: true)]
    protected ?int $recipientLikes = null;

    #[ORM\Column(name: 'facebook_likes', type: Types::INTEGER, nullable: true)]
    protected ?int $facebookLikes = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $updatedAt = null;

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
     * @param Channel $integration
     * @return Campaign
     */
    public function setChannel(Channel $integration)
    {
        $this->channel = $integration;

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
     * @return Organization
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param Organization $owner
     * @return Campaign
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return EmailCampaign
     */
    public function getEmailCampaign()
    {
        return $this->emailCampaign;
    }

    /**
     * @param EmailCampaign|null $emailCampaign
     * @return Campaign
     */
    public function setEmailCampaign(EmailCampaign $emailCampaign = null)
    {
        $this->emailCampaign = $emailCampaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @param string $fromEmail
     * @return Campaign
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;
        return $this;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param string $fromName
     * @return Campaign
     */
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return Campaign
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Campaign
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return int
     */
    public function getAbuseReports()
    {
        return $this->abuseReports;
    }

    /**
     * @param int $abuseReports
     * @return Campaign
     */
    public function setAbuseReports($abuseReports)
    {
        $this->abuseReports = $abuseReports;
        return $this;
    }

    /**
     * @return string
     */
    public function getArchiveUrl()
    {
        return $this->archiveUrl;
    }

    /**
     * @param string $archiveUrl
     * @return Campaign
     */
    public function setArchiveUrl($archiveUrl)
    {
        $this->archiveUrl = $archiveUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getArchiveUrlLong()
    {
        return $this->archiveUrlLong;
    }

    /**
     * @param string $archiveUrlLong
     * @return Campaign
     */
    public function setArchiveUrlLong($archiveUrlLong)
    {
        $this->archiveUrlLong = $archiveUrlLong;
        return $this;
    }

    /**
     * @return int
     */
    public function getClicks()
    {
        return $this->clicks;
    }

    /**
     * @param int $clicks
     * @return Campaign
     */
    public function setClicks($clicks)
    {
        $this->clicks = $clicks;
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return Campaign
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
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
     * @return Campaign
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getEmailsSent()
    {
        return $this->emailsSent;
    }

    /**
     * @param int $emailsSent
     * @return Campaign
     */
    public function setEmailsSent($emailsSent)
    {
        $this->emailsSent = $emailsSent;
        return $this;
    }

    /**
     * @return int
     */
    public function getFacebookLikes()
    {
        return $this->facebookLikes;
    }

    /**
     * @param int $facebookLikes
     * @return Campaign
     */
    public function setFacebookLikes($facebookLikes)
    {
        $this->facebookLikes = $facebookLikes;
        return $this;
    }

    /**
     * @return int
     */
    public function getForwards()
    {
        return $this->forwards;
    }

    /**
     * @param int $forwards
     * @return Campaign
     */
    public function setForwards($forwards)
    {
        $this->forwards = $forwards;
        return $this;
    }

    /**
     * @return int
     */
    public function getForwardsOpens()
    {
        return $this->forwardsOpens;
    }

    /**
     * @param int $forwardsOpens
     * @return Campaign
     */
    public function setForwardsOpens($forwardsOpens)
    {
        $this->forwardsOpens = $forwardsOpens;
        return $this;
    }

    /**
     * @return int
     */
    public function getHardBounces()
    {
        return $this->hardBounces;
    }

    /**
     * @param int $hardBounces
     * @return Campaign
     */
    public function setHardBounces($hardBounces)
    {
        $this->hardBounces = $hardBounces;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastOpenDate()
    {
        return $this->lastOpenDate;
    }

    /**
     * @param \DateTime $lastOpenDate
     * @return Campaign
     */
    public function setLastOpenDate($lastOpenDate)
    {
        $this->lastOpenDate = $lastOpenDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getOpens()
    {
        return $this->opens;
    }

    /**
     * @param int $opens
     * @return Campaign
     */
    public function setOpens($opens)
    {
        $this->opens = $opens;
        return $this;
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
     * @return Campaign
     */
    public function setOriginId($originId)
    {
        $this->originId = $originId;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecipientLikes()
    {
        return $this->recipientLikes;
    }

    /**
     * @param int $recipientLikes
     * @return Campaign
     */
    public function setRecipientLikes($recipientLikes)
    {
        $this->recipientLikes = $recipientLikes;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSendTime()
    {
        return $this->sendTime;
    }

    /**
     * @param \DateTime $sendTime
     * @return Campaign
     */
    public function setSendTime($sendTime)
    {
        $this->sendTime = $sendTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getSoftBounces()
    {
        return $this->softBounces;
    }

    /**
     * @param int $softBounces
     * @return Campaign
     */
    public function setSoftBounces($softBounces)
    {
        $this->softBounces = $softBounces;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     * @return Campaign
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return StaticSegment
     */
    public function getStaticSegment()
    {
        return $this->staticSegment;
    }

    /**
     * @param StaticSegment|null $segment
     * @return Campaign
     */
    public function setStaticSegment(StaticSegment $segment = null)
    {
        $this->staticSegment = $segment;
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
     * @return Campaign
     */
    public function setSubscribersList(SubscribersList $subscribersList = null)
    {
        $this->subscribersList = $subscribersList;
        return $this;
    }

    /**
     * @return int
     */
    public function getSyntaxErrors()
    {
        return $this->syntaxErrors;
    }

    /**
     * @param int $syntaxErrors
     * @return Campaign
     */
    public function setSyntaxErrors($syntaxErrors)
    {
        $this->syntaxErrors = $syntaxErrors;
        return $this;
    }

    /**
     * @return Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param Template $template
     * @return Campaign
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @return int
     */
    public function getTestsRemain()
    {
        return $this->testsRemain;
    }

    /**
     * @param int $testsRemain
     * @return Campaign
     */
    public function setTestsRemain($testsRemain)
    {
        $this->testsRemain = $testsRemain;
        return $this;
    }

    /**
     * @return int
     */
    public function getTestsSent()
    {
        return $this->testsSent;
    }

    /**
     * @param int $testsSent
     * @return Campaign
     */
    public function setTestsSent($testsSent)
    {
        $this->testsSent = $testsSent;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Campaign
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueClicks()
    {
        return $this->uniqueClicks;
    }

    /**
     * @param int $uniqueClicks
     * @return Campaign
     */
    public function setUniqueClicks($uniqueClicks)
    {
        $this->uniqueClicks = $uniqueClicks;
        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueLikes()
    {
        return $this->uniqueLikes;
    }

    /**
     * @param int $uniqueLikes
     * @return Campaign
     */
    public function setUniqueLikes($uniqueLikes)
    {
        $this->uniqueLikes = $uniqueLikes;
        return $this;
    }

    /**
     * @return int
     */
    public function getUniqueOpens()
    {
        return $this->uniqueOpens;
    }

    /**
     * @param int $uniqueOpens
     * @return Campaign
     */
    public function setUniqueOpens($uniqueOpens)
    {
        $this->uniqueOpens = $uniqueOpens;
        return $this;
    }

    /**
     * @return int
     */
    public function getUnsubscribes()
    {
        return $this->unsubscribes;
    }

    /**
     * @param int $unsubscribes
     * @return Campaign
     */
    public function setUnsubscribes($unsubscribes)
    {
        $this->unsubscribes = $unsubscribes;
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
     * @return Campaign
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * @return int
     */
    public function getUsersWhoClicked()
    {
        return $this->usersWhoClicked;
    }

    /**
     * @param int $usersWhoClicked
     * @return Campaign
     */
    public function setUsersWhoClicked($usersWhoClicked)
    {
        $this->usersWhoClicked = $usersWhoClicked;
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
     * @return Campaign
     */
    public function setWebId($webId)
    {
        $this->webId = $webId;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivityUpdateState()
    {
        if ($this->getEmailCampaign()
            && !$this->getEmailCampaign()->getTransportSettings()->getSettingsBag()->get('receiveActivities')
        ) {
            return self::ACTIVITY_DISABLED;
        }

        $updatesExpireDate = null;
        if ($this->getSendTime() instanceof \DateTime) {
            $updateInterval = $this->getChannel()
                ->getTransport()
                ->getSettingsBag()
                ->get('activityUpdateInterval');

            if ($updateInterval) {
                $updatesExpireDate = clone($this->getSendTime());
                $updatesExpireDate->add(new \DateInterval('P' . $updateInterval . 'D'));
            }
        }

        if ((bool)$updatesExpireDate
            && $updatesExpireDate < new \DateTime('now', new \DateTimeZone('UTC'))
        ) {
            return self::ACTIVITY_EXPIRED;
        }

        return self::ACTIVITY_ENABLED;
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
