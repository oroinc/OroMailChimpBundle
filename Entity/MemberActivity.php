<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Repository\MemberActivityRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Mailchimp member's activity entity class.
 *
 * @link http://apidocs.mailchimp.com/api/2.0/lists/member-activity.php
 */
#[ORM\Entity(repositoryClass: MemberActivityRepository::class)]
#[ORM\Table(name: 'orocrm_mc_mmbr_activity')]
#[ORM\Index(columns: ['action'], name: 'mc_mmbr_activity_action_idx')]
#[Config(
    defaultValues: [
        'entity' => ['icon' => 'fa-bar-chart-o'],
        'ownership' => [
            'owner_type' => 'ORGANIZATION',
            'owner_field_name' => 'owner',
            'owner_column_name' => 'owner_id'
        ],
        'security' => ['type' => 'ACL', 'group_name' => '', 'category' => 'marketing']
    ]
)]
class MemberActivity
{
    /**#@+
     * @const string Activity of Member Activity
     */
    const ACTIVITY_OPEN = 'open';
    const ACTIVITY_CLICK = 'click';
    const ACTIVITY_BOUNCE = 'bounce';
    const ACTIVITY_UNSUB = 'unsub';
    const ACTIVITY_ABUSE = 'abuse';
    const ACTIVITY_SENT = 'sent';
    const ACTIVITY_ECOMM = 'ecomm';
    const ACTIVITY_MANDRILL_SEND = 'mandrill_send';
    const ACTIVITY_MANDRILL_HARD_BOUNCE = 'mandrill_hard_bounce';
    const ACTIVITY_MANDRILL_SOFT_BOUNCE = 'mandrill_soft_bounce';
    const ACTIVITY_MANDRILL_OPEN = 'mandrill_open';
    const ACTIVITY_MANDRILL_CLICK = 'mandrill_click';
    const ACTIVITY_MANDRILL_SPAM = 'mandrill_spam';
    const ACTIVITY_MANDRILL_UNSUB = 'mandrill_unsub';
    const ACTIVITY_MANDRILL_REJECT = 'mandrill_reject';
    /**#@-*/
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Channel $channel = null;

    #[ORM\ManyToOne(targetEntity: Campaign::class)]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Campaign $campaign = null;

    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?Member $member = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Organization $owner = null;

    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $email = null;

    #[ORM\Column(name: 'action', type: Types::STRING, length: 25, nullable: false)]
    protected ?string $action = null;

    #[ORM\Column(name: 'ip_address', type: Types::STRING, length: 45, nullable: true)]
    protected ?string $ip = null;

    #[ORM\Column(name: 'activity_time', type: Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $activityTime = null;

    #[ORM\Column(name: 'url', type: Types::TEXT, nullable: true)]
    protected ?string $url = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     * @return MemberActivity
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign()
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     * @return MemberActivity
     */
    public function setCampaign($campaign)
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Member $member
     * @return MemberActivity
     */
    public function setMember($member)
    {
        $this->member = $member;

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
     * @return MemberActivity
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     * @return MemberActivity
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return MemberActivity
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getActivityTime()
    {
        return $this->activityTime;
    }

    /**
     * @param \DateTime $activityTime
     * @return MemberActivity
     */
    public function setActivityTime($activityTime)
    {
        $this->activityTime = $activityTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return MemberActivity
     */
    public function setUrl($url)
    {
        $this->url = $url;

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
     * @return MemberActivity
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }
}
