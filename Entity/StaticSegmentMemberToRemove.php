<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mailchimp static segment member to remove entity class.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_mc_tmp_mmbr_to_remove')]
#[ORM\Index(columns: ['state'], name: 'mc_smbr_rm_state_idx')]
class StaticSegmentMemberToRemove
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Member::class)]
    #[ORM\JoinColumn(name: 'member_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Member $member = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: StaticSegment::class)]
    #[ORM\JoinColumn(name: 'static_segment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?StaticSegment $staticSegment = null;

    #[ORM\Column(name: 'state', type: Types::STRING, length: 25, nullable: false)]
    protected ?string $state = StaticSegmentMember::STATE_REMOVE;

    /**
     * @return Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     * @param Member $member
     * @return StaticSegmentMemberToRemove
     */
    public function setMember(Member $member)
    {
        $this->member = $member;

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
     * @param StaticSegment $staticSegment
     * @return StaticSegmentMemberToRemove
     */
    public function setStaticSegment(StaticSegment $staticSegment)
    {
        $this->staticSegment = $staticSegment;

        return $this;
    }
}
