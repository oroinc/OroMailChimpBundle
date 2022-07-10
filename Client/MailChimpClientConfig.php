<?php

namespace Oro\Bundle\MailChimpBundle\Client;

/**
 * Stores additional client configuration
 */
class MailChimpClientConfig
{
    public const MERGE_FIELDS_COUNT_MIN = 10;
    public const MERGE_FIELDS_COUNT_MAX = 1000;

    public const MEMBER_SKIP_MERGE_VALIDATION = false;

    protected int $mergeFieldsCount = self::MERGE_FIELDS_COUNT_MIN;
    protected bool $memberSkipMergeValidation = self::MEMBER_SKIP_MERGE_VALIDATION;

    public function setMergeFieldsCount(int $mergeFieldsCount): self
    {
        $this->mergeFieldsCount = $mergeFieldsCount;
        return $this;
    }

    public function getMergeFieldsCount(): int
    {
        if ($this->mergeFieldsCount < self::MERGE_FIELDS_COUNT_MIN) {
            return self::MERGE_FIELDS_COUNT_MIN;
        }

        if ($this->mergeFieldsCount > self::MERGE_FIELDS_COUNT_MAX) {
            return self::MERGE_FIELDS_COUNT_MAX;
        }

        return $this->mergeFieldsCount;
    }

    public function setMemberSkipMergeValidation(bool $memberSkipMergeValidation): self
    {
        $this->memberSkipMergeValidation = $memberSkipMergeValidation;
        return $this;
    }

    public function getMemberSkipMergeValidation(): bool
    {
        return $this->memberSkipMergeValidation;
    }
}
