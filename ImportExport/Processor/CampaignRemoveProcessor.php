<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Processor;

/**
 * Batch job processor to remove mailchimp processed campaign items after import.
 */
class CampaignRemoveProcessor extends RemoveProcessor
{
    /**
     * @var string
     */
    protected $campaignType;

    /**
     * @param string $campaignType
     */
    public function setCampaignType($campaignType)
    {
        if (!is_string($campaignType) || empty($campaignType)) {
            throw new \InvalidArgumentException('CampaignType should be provided.');
        }

        $this->campaignType = $campaignType;
    }

    #[\Override]
    protected function createQueryBuilder(array $item)
    {
        if (!$this->campaignType) {
            throw new \InvalidArgumentException('CampaignType should be provided.');
        }

        $qb = parent::createQueryBuilder($item);

        $qb->andWhere($qb->expr()->eq('e.type', ':campaignType'))
            ->setParameter('campaignType', $this->campaignType);

        return $qb;
    }
}
