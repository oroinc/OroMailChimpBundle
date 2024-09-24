<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar\ProviderInterface;
use Oro\Bundle\MailChimpBundle\Util\CallbackFilterIteratorCompatible;

/**
 * Add extended merge variables iterator.
 */
class ExtendedMergeVarAddIterator extends AbstractSubordinateIterator
{
    /**
     * @var ProviderInterface
     */
    protected $provider;

    public function __construct(ProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function setMainIterator(\Iterator $mainIterator)
    {
        $this->mainIterator = $mainIterator;
    }

    /**
     * @param StaticSegment $staticSegment
     *
     */
    #[\Override]
    protected function createSubordinateIterator($staticSegment)
    {
        if (!$this->provider->isApplicable($staticSegment->getMarketingList())) {
            return new \EmptyIterator();
        }

        $vars = $this->provider->provideExtendedMergeVars($staticSegment->getMarketingList());

        $existingVars = $staticSegment
            ->getExtendedMergeVars([ExtendedMergeVar::STATE_ADD, ExtendedMergeVar::STATE_SYNCED])
            ->map(function (ExtendedMergeVar $extendedMergeVar) {
                return $extendedMergeVar->getName();
            })
            ->toArray();

        $newVars = array_filter(
            $vars,
            function ($var) use ($existingVars) {
                return !in_array($var['name'], $existingVars, true);
            }
        );

        return new CallbackFilterIteratorCompatible(
            new \ArrayIterator($newVars),
            function (&$current) use ($staticSegment) {
                if (is_array($current)) {
                    $current['static_segment_id'] = $staticSegment->getId();
                    $current['state'] = ExtendedMergeVar::STATE_ADD;
                }
                return true;
            }
        );
    }
}
