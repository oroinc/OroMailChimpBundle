<?php

namespace Oro\Bundle\MailChimpBundle\Client;

/**
 * Indicates that client has config dependency
 */
interface MailChimpClientConfigAwareInterface
{
    public function setConfig(MailChimpClientConfig $clientConfig);
}
