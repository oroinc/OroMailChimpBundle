<?php

namespace Oro\Bundle\MailChimpBundle\Transport;

use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\CampaignBundle\Transport\TransportInterface;
use Oro\Bundle\CampaignBundle\Transport\VisibilityTransportInterface;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings;
use Oro\Bundle\MailChimpBundle\Form\Type\MailChimpTransportSettingsType;

/**
 * Implements the transport to send mailchimp campaigns emails.
 */
class MailChimpTransport implements TransportInterface, VisibilityTransportInterface
{
    const NAME = 'mailchimp';

    #[\Override]
    public function send(EmailCampaign $campaign, object $entity, array $from, array $to)
    {
        // Implement send CRM-1980
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function getLabel()
    {
        return 'oro.mailchimp.emailcampaign.transport.' . self::NAME;
    }

    #[\Override]
    public function getSettingsFormType()
    {
        return MailChimpTransportSettingsType::class;
    }

    #[\Override]
    public function getSettingsEntityFQCN()
    {
        return MailChimpTransportSettings::class;
    }

    #[\Override]
    public function isVisibleInForm()
    {
        return false;
    }
}
