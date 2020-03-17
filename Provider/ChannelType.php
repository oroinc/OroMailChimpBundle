<?php

namespace Oro\Bundle\MailChimpBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Mailchimp integration channel type provider.
 */
class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'mailchimp';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.mailchimp.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oromailchimp/img/freddie.ico';
    }
}
