<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CampaignBundle\Entity\TransportSettings;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Entity class to extend integration transport settings with mailchimp related data.
 */
#[ORM\Entity]
class MailChimpTransportSettings extends TransportSettings
{
    #[ORM\ManyToOne(targetEntity: Channel::class)]
    #[ORM\JoinColumn(name: 'mailchimp_channel_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    protected ?Channel $channel = null;

    #[ORM\ManyToOne(targetEntity: Template::class)]
    #[ORM\JoinColumn(name: 'mailchimp_template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?Template $template = null;

    #[ORM\Column(name: 'mailchimp_receive_activities', type: Types::BOOLEAN)]
    protected ?bool $receiveActivities = true;

    /**
     * @return Channel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param Channel $channel
     * @return MailChimpTransportSettings
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Set template
     *
     * @param Template|null $emailTemplate
     *
     * @return MailChimpTransportSettings
     */
    public function setTemplate(Template $emailTemplate = null)
    {
        $this->template = $emailTemplate;

        return $this;
    }

    /**
     * Get template
     *
     * @return Template|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return boolean
     */
    public function isReceiveActivities()
    {
        return $this->receiveActivities;
    }

    /**
     * @param boolean $receiveActivities
     * @return MailChimpTransportSettings
     */
    public function setReceiveActivities($receiveActivities)
    {
        $this->receiveActivities = $receiveActivities;

        return $this;
    }

    #[\Override]
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag(
                [
                    'channel' => $this->getChannel(),
                    'receiveActivities' => $this->isReceiveActivities(),
                    // 'template' => $this->getTemplate()
                ]
            );
        }

        return $this->settings;
    }
}
