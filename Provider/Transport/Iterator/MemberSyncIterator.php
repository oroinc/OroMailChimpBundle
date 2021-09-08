<?php

namespace Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Writer\AbstractNativeQueryWriter;
use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Model\MergeVar\MergeVarProviderInterface;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\QueryDesignerBundle\Model\GroupByHelper;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Mailchimp members synchronized iterator.
 */
class MemberSyncIterator extends AbstractStaticSegmentMembersIterator
{
    /**
     * @var MergeVarProviderInterface
     */
    protected $mergeVarsProvider;

    /**
     * @var bool
     */
    protected $hasFirstName = false;

    /**
     * @var bool
     */
    protected $hasLastName = false;

    /**
     * @var string
     */
    protected $firstNameField;

    /**
     * @var string
     */
    protected $lastNameField;

    /**
     * @var array
     */
    protected $contactInformationFields;

    /**
     * @var DQLNameFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $extendMergeVarsClass;

    /**
     * @var GroupByHelper
     */
    protected $groupByHelper;

    /**
     * @param MergeVarProviderInterface $mergeVarsProvider
     * @return MemberSyncIterator
     */
    public function setMergeVarsProvider(MergeVarProviderInterface $mergeVarsProvider)
    {
        $this->mergeVarsProvider = $mergeVarsProvider;

        return $this;
    }

    /**
     * @param DQLNameFormatter $formatter
     * @return MemberSyncIterator
     */
    public function setFormatter(DQLNameFormatter $formatter)
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * @param string $extendMergeVarsClass
     * @return MemberSyncIterator
     */
    public function setExtendMergeVarsClass($extendMergeVarsClass)
    {
        $this->extendMergeVarsClass = $extendMergeVarsClass;

        return $this;
    }

    /**
     * @param GroupByHelper $groupByHelper
     *
     * @return MemberSyncIterator
     */
    public function setGroupByHelper($groupByHelper)
    {
        $this->groupByHelper = $groupByHelper;

        return $this;
    }

    /**
     * Return query builder instead of BufferedQueryResultIterator.
     *
     * {@inheritdoc}
     */
    protected function createSubordinateIterator($staticSegment)
    {
        $qb = $this->getIteratorQueryBuilder($staticSegment);

        return new \ArrayIterator(
            [
                [
                    AbstractNativeQueryWriter::QUERY_BUILDER => $qb,
                    'subscribers_list_id' => $staticSegment->getSubscribersList()->getId(),
                    'has_first_name' => $this->hasFirstName,
                    'has_last_name' => $this->hasLastName
                ]
            ]
        );
    }

    /**
     * Add required fields and filters members that are not in list yet.
     *
     * {@inheritdoc}
     */
    protected function getIteratorQueryBuilder(StaticSegment $staticSegment)
    {
        $qb = $this->getCommonIteratorQueryBuilder($staticSegment);
        // Select only members that are not in list yet
        $qb->andWhere($qb->expr()->isNull(sprintf('%s.id', self::MEMBER_ALIAS)));

        return $qb;
    }

    /**
     * Add required fields.
     *
     * Fields: first_name, last_name, email, owner_id, subscribers_list_id, channel_id, status, merge_var_values.
     *
     * @param StaticSegment $staticSegment
     * @return QueryBuilder
     * @throws \InvalidArgumentException
     */
    protected function getCommonIteratorQueryBuilder(StaticSegment $staticSegment)
    {
        $qb = parent::getIteratorQueryBuilder($staticSegment);

        $this->addNameFields($staticSegment->getMarketingList()->getEntity(), $qb);

        $subscribersListId = $staticSegment->getSubscribersList()->getId();
        $ownerId = $staticSegment->getOwner()->getId();
        $channelId = $staticSegment->getChannel()->getId();

        $qb->addSelect($ownerId . ' as owner_id');
        $qb->addSelect($subscribersListId . ' as subscribers_list_id');
        $qb->addSelect($channelId . ' as channel_id');
        $qb->addSelect(sprintf("'%s' as status", Member::STATUS_EXPORT));
        $qb->addSelect('CURRENT_TIMESTAMP() as created_at');

        $this->addMergeVars($qb, $staticSegment);

        return $qb;
    }

    /**
     * Always add first_name and last_name to select, as them will be used for INSERT FROM SELECT later.
     *
     * {@inheritdoc}
     */
    protected function addNameFields($entityName, QueryBuilder $qb)
    {
        /** @var From[] $from */
        $from = $qb->getDQLPart('from');
        $entityAlias = $from[0]->getAlias();
        $parts = $this->formatter->extractNamePartsPaths($entityName, $entityAlias);

        $this->hasFirstName = false;
        $this->firstNameField = null;
        if (isset($parts['first_name'])) {
            $this->hasFirstName = true;
            $this->firstNameField = $parts['first_name'];
            $qb->addSelect($this->firstNameField . ' as first_name');
        }

        $this->hasLastName = false;
        $this->lastNameField = null;
        if (isset($parts['last_name'])) {
            $this->hasLastName = true;
            $this->lastNameField = $parts['last_name'];
            $qb->addSelect($this->lastNameField . ' as last_name');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getContactInformationFields(MarketingList $marketingList)
    {
        $this->contactInformationFields = parent::getContactInformationFields($marketingList);

        return $this->contactInformationFields;
    }

    /**
     * Add merge prepared for insertion merge vars column.
     *
     * @throws DBALException
     */
    protected function addMergeVars(QueryBuilder $qb, StaticSegment $staticSegment)
    {
        $columnInformation = $this->marketingListProvider->getColumnInformation($staticSegment->getMarketingList());
        $extendMergeVars = $this->getExtendMergeVars($qb->getEntityManager());

        /** @var ExtendedMergeVar[] $extendMergeVars */
        $extendMergeVars = array_filter(
            $extendMergeVars,
            static function (ExtendedMergeVar $mergeVar) use ($columnInformation) {
                return array_key_exists($mergeVar->getName(), $columnInformation);
            }
        );

        $hasTagField = ArrayUtil::some(
            function (ExtendedMergeVar $var) {
                return $var->getName() === 'tag_field';
            },
            $extendMergeVars
        );
        if ($hasTagField) {
            $qb
                ->join(
                    'OroTagBundle:Tagging',
                    'tagging',
                    'WITH',
                    'tagging.entityName = :entity_name AND tagging.recordId = t1.id'
                )
                ->join('tagging.tag', 'tag')
                ->setParameter('entity_name', $staticSegment->getMarketingList()->getEntity());
            $columnInformation['tag_field'] = 'tag.name';
        }

        $mergeVarsData = $this->getMergeVarsData($qb, $staticSegment);
        foreach ($extendMergeVars as $mergeVar) {
            $mergeVarsData[] = "'" . $mergeVar->getTag() . "'";
            $mergeVarsData[] = $columnInformation[$mergeVar->getName()];
        }

        if ($mergeVarsData) {
            $mergeVarsExpr = 'json_build_object(' . implode(', ', $mergeVarsData) . ') as merge_vars';
            $qb->addSelect($mergeVarsExpr);
        }

        $groupBy = $this->getGroupBy($qb);
        if ($groupBy) {
            $qb->addGroupBy(implode(',', $groupBy));
        }
    }

    protected function getMergeVarsData(QueryBuilder $qb, StaticSegment $staticSegment): array
    {
        $mergeVarFields = $this->mergeVarsProvider->getMergeVarFields($staticSegment->getSubscribersList());
        $data = [];

        // Prepare merge vars data
        if ($mergeVarFields->getEmail()) {
            $data[] = "'" . $mergeVarFields->getEmail()->getTag() . "'";
            $data[] = $this->getEmailFieldExpression($qb, $staticSegment);
        }
        if ($mergeVarFields->getFirstName()) {
            $data[] = "'" . $mergeVarFields->getFirstName()->getTag() . "'";
            $data[] = $this->firstNameField;
        }
        if ($mergeVarFields->getLastName()) {
            $data[] = "'" . $mergeVarFields->getLastName()->getTag() . "'";
            $data[] = $this->lastNameField;
        }

        return $data;
    }

    /**
     * @param QueryBuilder $qb
     * @param StaticSegment $staticSegment
     * @return string
     */
    protected function getEmailFieldExpression(QueryBuilder $qb, StaticSegment $staticSegment)
    {
        $emailField = reset($this->contactInformationFields);

        return $this->fieldHelper
            ->getFieldExpr($staticSegment->getMarketingList()->getEntity(), $qb, $emailField);
    }

    /**
     * @param EntityManager $entityManager
     * @return ExtendedMergeVar[]
     */
    protected function getExtendMergeVars(EntityManager $entityManager)
    {
        return $entityManager->getRepository($this->extendMergeVarsClass)
            ->findAll();
    }

    protected function getGroupBy(QueryBuilder $qb): array
    {
        $orderByPartItems = [];
        foreach ($qb->getDQLPart('orderBy') as $orderByPart) {
            $orderByPartItems[] = trim(preg_replace('/(ASC|DESC)$/i', '', $orderByPart));
        }

        return $this->groupByHelper->getGroupByFields(
            $qb->getDQLPart('groupBy'),
            array_merge($qb->getDQLPart('select'), $orderByPartItems)
        );
    }
}
