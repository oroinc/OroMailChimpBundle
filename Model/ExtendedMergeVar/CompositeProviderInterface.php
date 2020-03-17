<?php

namespace Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar;

/**
 * Extended merge variables composite provider interface.
 */
interface CompositeProviderInterface extends ProviderInterface
{
    /**
     * Adds external provider
     *
     * @param ProviderInterface $provider
     * @return void
     */
    public function addProvider(ProviderInterface $provider);
}
