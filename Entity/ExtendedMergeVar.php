<?php

namespace Oro\Bundle\MailChimpBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;

/**
 * Mailchimp extended merge variable((single cell commulated additional data)) entity class.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orocrm_mc_extended_merge_var')]
#[ORM\UniqueConstraint(name: 'mc_emv_sid_name_unq', columns: ['static_segment_id', 'name'])]
#[ORM\HasLifecycleCallbacks]
#[Config]
class ExtendedMergeVar
{
    const STATE_ADD = 'add';
    const STATE_REMOVE = 'remove';
    const STATE_SYNCED = 'synced';
    const STATE_DROPPED = 'dropped';

    const TAG_TEXT_FIELD_TYPE = 'text';
    const TAG_NUMBER_FIELD_TYPE = 'number';
    const TAG_DATE_FIELD_TYPE = 'date';

    const TAG_PREFIX = 'E_';
    const MAXIMUM_TAG_LENGTH = 10;

    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: StaticSegment::class, inversedBy: 'extendedMergeVars')]
    #[ORM\JoinColumn(name: 'static_segment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?StaticSegment $staticSegment = null;

    #[ORM\Column(name: 'name', type: Types::STRING, length: 255, nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $label = null;

    #[ORM\Column(name: 'is_required', type: Types::BOOLEAN)]
    protected ?bool $required = null;

    #[ORM\Column(name: 'field_type', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $fieldType = null;

    #[ORM\Column(name: 'tag', type: Types::STRING, length: 10, nullable: false)]
    protected ?string $tag = null;

    #[ORM\Column(name: 'state', type: Types::STRING, length: 255, nullable: false)]
    protected ?string $state = null;

    /**
     * Initialize default values for the entity
     */
    public function __construct()
    {
        $this->required = false;
        $this->fieldType = self::TAG_TEXT_FIELD_TYPE;
        $this->state = self::STATE_ADD;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return StaticSegment
     */
    public function getStaticSegment()
    {
        return $this->staticSegment;
    }

    /**
     * @param StaticSegment $staticSegment
     * @return ExtendedMergeVar
     */
    public function setStaticSegment(StaticSegment $staticSegment)
    {
        $this->staticSegment = $staticSegment;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ExtendedMergeVar
     */
    public function setName($name)
    {
        if (!is_string($name) || empty($name)) {
            throw new \InvalidArgumentException('Name must be not empty string.');
        }
        if ($name !== $this->name) {
            $this->generateTag($name);
        }
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return ExtendedMergeVar
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param $state
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Check if in the add state
     *
     * @return bool
     */
    public function isAddState()
    {
        return self::STATE_ADD === $this->state;
    }

    /**
     * @return bool
     */
    public function isRemoveState()
    {
        return self::STATE_REMOVE === $this->state;
    }

    /**
     * @return void
     */
    public function markSynced()
    {
        $this->state = self::STATE_SYNCED;
    }

    /**
     * @return void
     */
    public function markDropped()
    {
        $this->state = self::STATE_DROPPED;
    }

    /**
     * @param string $name
     * @return void
     */
    protected function generateTag($name)
    {
        // if its related entity, extract only attribute name
        // customer+Oro\Bundle\CustomerBundle\Entity\Customer::internal_rating -> internal_rating
        if (str_contains($name, '\\') && str_contains($name, '::')) {
            $name = explode('::', $name);
            $name = end($name);
        }

        $tag = self::TAG_PREFIX . strtoupper($name);
        if (strlen($tag) > self::MAXIMUM_TAG_LENGTH) {
            $tag = preg_replace('#[aeiou\s]+#i', '', $name);
            $tag = self::TAG_PREFIX . strtoupper($tag);
            if (strlen($tag) > self::MAXIMUM_TAG_LENGTH) {
                $tag = substr($tag, 0, self::MAXIMUM_TAG_LENGTH);
            }
        }
        $this->tag = $tag;
    }
}
