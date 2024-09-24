<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\DataConverter;

/**
 * Data converter to export/import format for mailchimp campaing data.
 */
class CampaignDataConverter extends IntegrationAwareDataConverter
{
    #[\Override]
    protected function getHeaderConversionRules()
    {
        return [
            // MailChimp campaign fields
            'id' => 'originId',
            'web_id' => 'webId',
            'subject_line' => 'subject',
            'type' => 'type',
            'list_id' => 'subscribersList:originId',
            'content_type' => 'contentType',
            'create_time' => 'createdAt',
            'archive_url' => 'archiveUrl',
            'long_archive_url' => 'archiveUrlLong',

            // Email campaign related
            'send_time' => 'sendTime',
            'from_name' => 'fromName',
            'reply_to' => 'fromEmail',

            // MailChimp campaign Summary
            'emails_sent' => 'emailsSent',
            'last_open' => 'lastOpenDate',
            'syntax_errors' => 'syntaxErrors',
            'hard_bounces' => 'hardBounces',
            'soft_bounces' => 'softBounces',
            'abuse_reports' => 'abuseReports',
            'forwards_count' => 'forwards',
            'forwards_opens' => 'forwardsOpens',
            'unique_opens' => 'uniqueOpens',
            'unique_clicks' => 'uniqueClicks',
            'clicks' => 'usersWhoClicked',
            'clicks_total' => 'clicks',
            'unique_likes' => 'uniqueLikes',
            'recipient_likes' => 'recipientLikes',
            'facebook_likes' => 'facebookLikes',
            'unsubscribed' => 'unsubscribes',
        ];
    }

    #[\Override]
    public function convertToImportFormat(array $importedRecord, $skipNullValues = true)
    {
        $channel = $this->context->getOption('channel');
        if (array_key_exists('_links', $importedRecord)) {
            unset($importedRecord['_links']);
        }
        if (is_array($importedRecord['report_summary'])) {
            $importedRecord = array_merge($importedRecord, $importedRecord['report_summary']);
            unset($importedRecord['report_summary']);
        }

        if (is_array($importedRecord['settings'])) {
            $importedRecord = array_merge($importedRecord, $importedRecord['settings']);
            unset($importedRecord['settings']);
        }

        if (empty($importedRecord['title'])) {
            $importedRecord['title'] = $importedRecord['subject_line'];
        }

        if (is_array($importedRecord['recipients'])) {
            $importedRecord = array_merge($importedRecord, $importedRecord['recipients']);
            unset($importedRecord['recipients']);
        }

        $importedRecord = $this->mergeReport($importedRecord);

        if (isset($importedRecord['segment_opts']['saved_segment_id'])) {
            $importedRecord['staticSegment:originId'] = $importedRecord['segment_opts']['saved_segment_id'];
            $importedRecord['staticSegment:channel:id'] = $channel;
        }

        $importedRecord['subscribersList:channel:id'] = $channel;
        return parent::convertToImportFormat($importedRecord, $skipNullValues);
    }

    /**
     * @param array $importedRecord
     * @return array
     */
    public function mergeReport(array $importedRecord)
    {
        if (false === array_key_exists('report', $importedRecord) ||
            false === is_array($importedRecord['report'])
        ) {
            return $importedRecord;
        }

        $report = $importedRecord['report'];
        unset($importedRecord['report']);
        if (array_key_exists('_links', $report)) {
            unset($report['_links']);
        }

        $keys = [
            'opens',
            'clicks',
            'facebook_likes',
            'bounces',
            'forwards',
            'abuse_reports',
            'unsubscribed',
        ];

        foreach ($keys as $key) {
            if (false === array_key_exists($key, $report)) {
                continue;
            }

            if (is_array($report[$key])) {
                $importedRecord = array_merge($importedRecord, $report[$key]);
            } else {
                $importedRecord[$key] = $report[$key];
            }
            unset($report[$key]);
        }

        return $importedRecord;
    }

    #[\Override]
    protected function getBackendHeader()
    {
        throw new \Exception('Normalization is not implemented!');
    }
}
