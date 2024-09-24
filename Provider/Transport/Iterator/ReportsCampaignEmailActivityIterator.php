<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

/**
 * Mailchimp campaign email activity iterator.
 */
class ReportsCampaignEmailActivityIterator extends AbstractMailChimpIterator
{
    private string $campaignId;
    private array $options = [];

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    #[\Override]
    protected function getResult()
    {
        $options = array_merge(
            [
                'campaign_id' => $this->campaignId,
                'count' => $this->batchSize,
                'offset' => $this->offset / $this->batchSize
            ],
            $this->options
        );

        $result = $this->client->getCampaignEmailActivityReport($options);
        $emails = $this->explodeByActivity($result['emails']);

        return [
            'data' => $emails,
            'campaignId' => $result['campaign_id'],
            'total' => count($emails),
        ];
    }

    private function explodeByActivity(array $emails): array
    {
        $data = [];
        foreach ($emails as $email) {
            $originalEmail = $email;
            if (empty($email['activity'])) {
                $data[] = $originalEmail;
            } else {
                foreach ($email['activity'] as $activity) {
                    $originalEmail['activity'] = $activity;
                    $data[] = $originalEmail;
                }
            }
        }

        return $data;
    }
}
