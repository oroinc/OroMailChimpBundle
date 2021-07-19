<?php

namespace Oro\Bundle\MailChimpBundle\ImportExport\Writer;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\Item\ItemWriterInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\QueryWithOptionsIterator;

/**
 * Batch job's writer to update member's data if its source(contact, customer user, etc.) has been updated
 * and then to change member's status to be exported into mailchimp.
 */
class UpdatedMemberSyncWriter implements ItemWriterInterface
{
    /**
     * @var bool
     */
    protected $hasFirstName = false;

    /**
     * @var bool
     */
    protected $hasLastName = false;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        foreach ($items as $item) {
            $updateQb = $this->getUpdateMemberQueryBuilder($item);

            $resultItems = isset($item[QueryWithOptionsIterator::RESULT_ITEMS]) ?
                $item[QueryWithOptionsIterator::RESULT_ITEMS] : [];

            foreach ($resultItems as $result) {
                $this->setUpdateMemberQueryParams($updateQb, $result);
                $updateQb->getQuery()->execute();
            }
        }
    }

    /**
     * @return QueryBuilder
     */
    private function getUpdateMemberQueryBuilder(array $item)
    {
        $this->hasFirstName = !empty($item['has_first_name']);
        $this->hasLastName = !empty($item['has_last_name']);

        /** @var QueryBuilder $qb */
        $qb = $this->doctrineHelper
            ->getEntityRepository(Member::class)
            ->createQueryBuilder('e')
            ->update();

        foreach ($this->getFields() as $field) {
            $qb->set('e.' . $field, ':' . $field);
        }
        $qb->where($qb->expr()->eq('e.id', ':id'));

        return $qb;
    }

    /**
     * Updates given members update query builder with given parameters
     *
     * @param QueryBuilder $qb
     * @param array $params
     * @return $this
     */
    private function setUpdateMemberQueryParams(QueryBuilder $qb, array $params)
    {
        $index = 0;
        $fields = $this->getFields();
        foreach ($params as $paramValue) {
            $qb->setParameter($fields[$index], $paramValue);
            $index++;
        }

        return $this;
    }

    /**
     * Gets member table's fields to be updated.
     * Fields amount and their order are strict to select query source.
     *
     * @return array
     */
    private function getFields()
    {
        $contactInformationFields = ['email'];
        if ($this->hasFirstName) {
            $contactInformationFields[] = 'firstName';
        }
        if ($this->hasLastName) {
            $contactInformationFields[] = 'lastName';
        }

        return array_merge(
            $contactInformationFields,
            [
                'owner',
                'subscribersList',
                'channel',
                'status',
                'updatedAt',
                'mergeVarValues',
                'id'
            ]
        );
    }
}
