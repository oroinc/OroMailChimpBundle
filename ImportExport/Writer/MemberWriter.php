<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Exception;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Exception\MailChimpClientException;
use Psr\Log\LoggerInterface;

/**
 * Batch job's mailchimp member writer.
 */
class MemberWriter extends AbstractExportWriter
{
    /**
     * Members that are batch subscribed to list are not immediately processed by the MailChimp.
     * To allow segments processing we need to wait some time to be sure that all records are available.
     *
     * @var int
     */
    private int $waitTime = 0;

    public function setWaitTime(int $waitTime): void
    {
        $this->waitTime = $waitTime;
    }

    public function getWaitTime(): int
    {
        return $this->waitTime;
    }

    /**
     * @param Member[] $items
     *
     */
    #[\Override]
    public function write(array $items)
    {
        /** @var Member $item */
        $item = $items[0];
        $this->transport->init($item->getChannel()->getTransport());

        $remoteMergeVars = $this->getSubscribersListMergeVars($item->getSubscribersList());
        $item->getSubscribersList()->setMergeVarConfig($remoteMergeVars);
        $remoteMergeVarsTags = array_map(function (array $var) {
            return $var['tag'];
        }, $remoteMergeVars);

        $membersBySubscriberList = [];
        foreach ($items as $member) {
            $member = $this->filterMergeVars($member, $remoteMergeVarsTags);
            $membersBySubscriberList[$member->getSubscribersList()->getOriginId()][] = $member;
        }

        foreach ($membersBySubscriberList as $subscribersListOriginId => $members) {
            $this->batchSubscribe($subscribersListOriginId, $members);
        }

        array_walk(
            $items,
            static function (Member $member) {
                if ($member->getStatus() === Member::STATUS_EXPORT) {
                    $member->setStatus(Member::STATUS_EXPORT_FAILED);
                }
            }
        );

        parent::write($items);

        $this->logger->info(sprintf('%d members processed', count($items)));
        $this->stepExecution->setWriteCount($this->stepExecution->getWriteCount() + count($items));

        $this->waitForProcessing();
    }

    protected function waitForProcessing(): void
    {
        if ($this->waitTime) {
            sleep($this->waitTime);
        }
    }

    /**
     * @param string $subscribersListOriginId
     * @param Member[] $items
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function batchSubscribe($subscribersListOriginId, array $items)
    {
        $emails = [];
        $members = array_map(
            function (Member $member) use (&$emails) {
                $email = $member->getEmail();
                $emails[] = mb_strtolower($email);

                $return = [
                    'email_address' => $email,
                    'status' => 'subscribed',
                ];

                $mergeFields = $member->getMergeVarValues();
                if (is_array($mergeFields) && count($mergeFields) > 0) {
                    $return['merge_fields'] = $mergeFields;
                }

                return $return;
            },
            $items
        );

        $items = array_combine($emails, $items);
        $requestParams = [
            'list_id' => $subscribersListOriginId,
            'members' => $members,
            'double_optin' => false,
            'update_existing' => true,
        ];

        $response = $this->transport->batchSubscribe($requestParams);
        $this
            ->handleResponse(
                $response,
                function ($response, LoggerInterface $logger) use ($subscribersListOriginId, $requestParams) {
                    $logger->info(
                        sprintf(
                            'List [origin_id=%s]: [%s] add, [%s] update, [%s] error',
                            $subscribersListOriginId,
                            $response['total_created'],
                            $response['total_updated'],
                            $response['error_count']
                        )
                    );

                    if (!empty($response['errors']) && is_array($response['errors'])) {
                        $criticalErrorMessages = array_filter($response['errors'], function ($err) {
                            if (!array_key_exists('error', $err)) {
                                return true;
                            }

                            if (str_contains($err['error'], 'fake')) {
                                return false;
                            }

                            if (str_contains($err['error'], 'has signed up to a lot of lists very recently')) {
                                return false;
                            }

                            if (str_contains($err['error'], 'valid') && !str_contains($err['error'], 'invalid')) {
                                return false;
                            }

                            return true;
                        });

                        if (empty($criticalErrorMessages)) {
                            $logger->warning('Mailchimp warning occurs during execution "batchSubscribe" method');
                        } else {
                            $msg = 'Mailchimp error occurs during execution "batchSubscribe" method';
                            $logger->error($msg);

                            # Mark sync as failed if no records were synced and there were errors.
                            if (empty($response['total_created']) && empty($response['total_updated'])) {
                                $this->stepExecution->addFailureException(new MailChimpClientException(
                                    'No records were synced during execution of the "batchSubscribe" method'
                                ));
                            }
                        }

                        $logger->debug('Mailchimp error occurs during execution "batchSubscribe" method', [
                            'requestParams' => $requestParams,
                            'response' => $response
                        ]);
                    }
                }
            );

        $emailsAdded = $this->getArrayData($response, 'new_members');
        $emailsUpdated = $this->getArrayData($response, 'updated_members');

        foreach (array_merge($emailsAdded, $emailsUpdated) as $emailData) {
            $emailAddress = mb_strtolower($emailData['email_address']);
            if (!array_key_exists($emailAddress, $items)) {
                $this->logger->alert(
                    sprintf('A member with "%s" email was not found', $emailData['email_address'])
                );

                continue;
            }

            /** @var Member $member */
            $member = $items[$emailAddress];

            $member
                ->setEuid($emailData['unique_email_id'])
                ->setOriginId($emailData['id'])
                ->setStatus(Member::STATUS_SUBSCRIBED);

            $this->logger->debug(
                sprintf('Member with data "%s" successfully processed', json_encode($emailData))
            );
        }
    }

    /**
     * @param Member $member
     * @param array $remoteMergeVarTags
     *
     * @return Member
     */
    protected function filterMergeVars(Member $member, array $remoteMergeVarTags)
    {
        return $member->setMergeVarValues(array_filter(
            $member->getMergeVarValues(),
            static function ($value, $key) use ($remoteMergeVarTags) {
                return $value !== null && in_array($key, $remoteMergeVarTags, true);
            },
            ARRAY_FILTER_USE_BOTH
        ));
    }
}
