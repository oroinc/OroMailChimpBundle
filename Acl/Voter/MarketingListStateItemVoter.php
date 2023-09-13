<?php

namespace Oro\Bundle\MailChimpBundle\Acl\Voter;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\SecurityBundle\Acl\Voter\AbstractEntityVoter;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Marketing list state item ACL voter class.
 */
class MarketingListStateItemVoter extends AbstractEntityVoter implements ServiceSubscriberInterface
{
    /** {@inheritDoc} */
    protected $supportedAttributes = ['DELETE'];

    private ContainerInterface $container;

    public function __construct(DoctrineHelper $doctrineHelper, ContainerInterface $container)
    {
        parent::__construct($doctrineHelper);
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'oro_marketing_list.provider.contact_information_fields' => ContactInformationFieldsProvider::class
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getPermissionForAttribute($class, $identifier, $attribute)
    {
        /** @var MarketingListStateItemInterface $item */
        $item = $this->doctrineHelper->getEntityRepository($this->className)->find($identifier);
        $entityClass = $item->getMarketingList()->getEntity();
        $entity = $this->doctrineHelper->getEntityRepository($entityClass)->find($item->getEntityId());

        if (!$entity) {
            return self::ACCESS_ABSTAIN;
        }

        $contactInformationFieldsProvider = $this->getContactInformationFieldsProvider();
        $contactInformationFields = $contactInformationFieldsProvider->getEntityTypedFields(
            $entityClass,
            ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL
        );
        $contactInformationValues = $contactInformationFieldsProvider->getTypedFieldsValues(
            $contactInformationFields,
            $entity
        );

        $result = $this->getQueryBuilder($contactInformationValues, $item)->getQuery()->getScalarResult();
        if (!empty($result)) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_ABSTAIN;
    }

    private function getQueryBuilder(
        array $contactInformationValues,
        MarketingListStateItemInterface $item
    ): QueryBuilder {
        $qb = $this->doctrineHelper->getEntityManager(Member::class)->createQueryBuilder();
        $qb
            ->setMaxResults(1)
            ->select('mmb.id')
            ->from(SubscribersList::class, 'subscribersList')
            ->join(Member::class, 'mmb', Join::WITH, 'mmb.subscribersList = subscribersList.id')
            ->join(MarketingList::class, 'ml', Join::WITH, 'mmb.subscribersList = subscribersList.id')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('ml.id', ':marketingListId'),
                    $qb->expr()->in('mmb.email', ':contactInformationValues'),
                    $qb->expr()->in('mmb.status', ':statuses')
                )
            )
            ->setParameter('marketingListId', $item->getMarketingList()->getId())
            ->setParameter('contactInformationValues', $contactInformationValues)
            ->setParameter('statuses', [Member::STATUS_UNSUBSCRIBED, Member::STATUS_CLEANED]);

        return $qb;
    }

    private function getContactInformationFieldsProvider(): ContactInformationFieldsProvider
    {
        return $this->container->get('oro_marketing_list.provider.contact_information_fields');
    }
}
