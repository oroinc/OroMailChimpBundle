<?php

namespace Oro\Bundle\MailChimpBundle\Model\MergeVar;

/**
 * Merge variable(single one) data holder class.
 */
class MergeVar implements MergeVarInterface
{
    /**
     * @var array
     */
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    #[\Override]
    public function getName()
    {
        return $this->getDataValue(self::PROPERTY_NAME);
    }

    #[\Override]
    public function getFieldType()
    {
        return $this->getDataValue(self::PROPERTY_FIELD_TYPE);
    }

    #[\Override]
    public function getTag()
    {
        return $this->getDataValue(self::PROPERTY_TAG);
    }

    #[\Override]
    public function isFirstName()
    {
        return $this->getTag() === self::TAG_FIRST_NAME;
    }

    #[\Override]
    public function isLastName()
    {
        return $this->getTag() === self::TAG_LAST_NAME;
    }

    #[\Override]
    public function isEmail()
    {
        return $this->getTag() === self::TAG_EMAIL;
    }

    #[\Override]
    public function isPhone()
    {
        return $this->getFieldType() === self::FIELD_TYPE_PHONE;
    }

    protected function getDataValue($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }
}
