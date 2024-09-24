<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Reader;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\MmbrExtdMergeVarExportIterator;

/**
 * Export batch job's reader for extended merge variables of mailchimp member.
 */
class MemberExtendedMergeVarExportReader extends AbstractExtendedMergeVarExportReader
{
    /**
     * @var string
     */
    protected $mmbrExtdMergeVarClassName;

    /**
     * @param string $mmbrExtdMergeVarClassName
     */
    public function setMmbrExtdMergeVarClassName($mmbrExtdMergeVarClassName)
    {
        $this->mmbrExtdMergeVarClassName = $mmbrExtdMergeVarClassName;
    }

    /**
     * @param Channel $channel
     * @return MmbrExtdMergeVarExportIterator
     * @throws \InvalidArgumentException if MemberExtendedMergeVar class name is not provided
     */
    #[\Override]
    protected function getExtendedMergeVarIterator(Channel $channel)
    {
        if (!is_string($this->mmbrExtdMergeVarClassName) && empty($this->mmbrExtdMergeVarClassName)) {
            throw new \InvalidArgumentException('MemberExtendedMergeVar class name must be provided.');
        }

        $iterator = new MmbrExtdMergeVarExportIterator(
            $this->getSegmentsIterator($channel),
            $this->doctrineHelper,
            $this->mmbrExtdMergeVarClassName
        );
        return $iterator;
    }
}
