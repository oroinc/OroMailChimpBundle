<?php

namespace Oro\Bundle\MailChimpBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar\ProviderInterface;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Marketing list extended merge variables provider.
 */
class MarketingListExtendedMergeVarProvider implements ProviderInterface
{
    /** @var EntityFieldProvider */
    protected $entityFieldProvider;

    /** @var array */
    protected $fieldTypeToMergeVarType = [
        'date' => ExtendedMergeVar::TAG_DATE_FIELD_TYPE,
        'integer' => ExtendedMergeVar::TAG_NUMBER_FIELD_TYPE,
        'float' => ExtendedMergeVar::TAG_NUMBER_FIELD_TYPE,
        'bigint' => ExtendedMergeVar::TAG_NUMBER_FIELD_TYPE,
    ];

    public function __construct(EntityFieldProvider $entityFieldProvider)
    {
        $this->entityFieldProvider = $entityFieldProvider;
    }

    #[\Override]
    public function isApplicable(MarketingList $marketingList)
    {
        return true;
    }

    #[\Override]
    public function provideExtendedMergeVars(MarketingList $marketingList)
    {
        $definition = QueryDefinitionUtil::decodeDefinition($marketingList->getDefinition());

        $fields = $this->entityFieldProvider->getEntityFields(
            $marketingList->getEntity(),
            EntityFieldProvider::OPTION_WITH_RELATIONS
            | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
            | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
            | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
            | EntityFieldProvider::OPTION_TRANSLATE
        );

        return $this->convertColumnsDefinitionToExtendMergeVars($definition['columns'], $fields);
    }

    /**
     * @param array $columnsDefinition
     * @param array $fields
     *
     * @return array
     */
    protected function convertColumnsDefinitionToExtendMergeVars(array $columnsDefinition, array $fields)
    {
        return array_map(
            function ($column) use ($fields) {
                $var = array_intersect_key($column, ['name' => null, 'label' => null]);

                $fieldType = $this->getFieldType($var['name'], $fields);
                if ($fieldType) {
                    $var['fieldType'] = $fieldType;
                }

                return $var;
            },
            $columnsDefinition
        );
    }

    /**
     * @param string $fieldName
     * @param array $fields
     *
     * @return string|null
     */
    protected function getFieldType($fieldName, array $fields)
    {
        $field = ArrayUtil::find(
            function (array $field) use ($fieldName) {
                return $field['name'] === $fieldName;
            },
            $fields
        );

        if ($field && isset($this->fieldTypeToMergeVarType[$field['type']])) {
            return $this->fieldTypeToMergeVarType[$field['type']];
        }

        return null;
    }
}
