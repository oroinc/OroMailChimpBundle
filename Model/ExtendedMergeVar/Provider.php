<?php

namespace Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar;

use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

/**
 * Merge variables provider.
 */
class Provider implements CompositeProviderInterface
{
    /**
     * @var array|ProviderInterface[]
     */
    protected $providers = [];

    #[\Override]
    public function addProvider(ProviderInterface $provider)
    {
        if (in_array($provider, $this->providers, true)) {
            return;
        }
        $this->providers[] = $provider;
    }

    #[\Override]
    public function isApplicable(MarketingList $marketingList)
    {
        foreach ($this->providers as $provider) {
            if ($provider->isApplicable($marketingList)) {
                return true;
            }
        }

        return false;
    }

    #[\Override]
    public function provideExtendedMergeVars(MarketingList $marketingList)
    {
        $vars = [];
        foreach ($this->providers as $provider) {
            $currentProviderVars = $provider->provideExtendedMergeVars($marketingList);
            if (!empty($currentProviderVars)) {
                $vars = array_merge($vars, $currentProviderVars);
            }
        }

        return $vars;
    }
}
