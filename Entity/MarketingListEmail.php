<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\MailChimpBundle\Entity\Repository\MarketingListEmailRepository;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Mailchimp marketing list email entity class.
 */
#[ORM\Entity(repositoryClass: MarketingListEmailRepository::class)]
#[ORM\Table(name: 'orocrm_mailchimp_ml_email')]
#[ORM\Index(columns: ['email'], name: 'mc_ml_email_idx')]
#[ORM\Index(columns: ['state'], name: 'mc_ml_email_state_idx')]
class MarketingListEmail
{
    public const STATE_IN_LIST = 'in_list';

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: MarketingList::class)]
    #[ORM\JoinColumn(name: 'marketing_list_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?MarketingList $marketingList = null;

    #[ORM\Id]
    #[ORM\Column(name: 'email', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $email = null;

    #[ORM\Column(name: 'state', type: Types::STRING, length: 25, nullable: false)]
    protected ?string $state = self::STATE_IN_LIST;

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return MarketingListEmail
     */
    public function setState($state)
    {
        $this->state = $state;

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
     * @return MarketingListEmail
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return MarketingList
     */
    public function getMarketingList()
    {
        return $this->marketingList;
    }

    /**
     * @param MarketingList $marketingList
     * @return MarketingListEmail
     */
    public function setMarketingList(MarketingList $marketingList)
    {
        $this->marketingList = $marketingList;

        return $this;
    }
}
