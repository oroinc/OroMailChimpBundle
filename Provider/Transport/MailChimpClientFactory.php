<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport;

use Oro\Bundle\MailChimpBundle\Client\MailChimpClientConfig;
use Oro\Bundle\MailChimpBundle\Client\MailChimpClientConfigAwareInterface;

/**
 * Mailchimp API client factory.
 */
class MailChimpClientFactory
{
    protected string $clientClass = MailChimpClient::class;
    protected ?MailChimpClientConfig $clientConfig = null;

    public function setClientClass(string $clientClass): void
    {
        $this->clientClass = $clientClass;
    }

    public function setClientConfig(MailChimpClientConfig $clientConfig): void
    {
        $this->clientConfig = $clientConfig;
    }

    /**
     * Create MailChimp Client.
     *
     * @param string $apiKey
     *
     * @return MailChimpClient
     */
    public function create($apiKey)
    {
        $client = new $this->clientClass($apiKey);

        if ($this->clientConfig && $client instanceof MailChimpClientConfigAwareInterface) {
            $client->setConfig($this->clientConfig);
        }

        return $client;
    }
}
