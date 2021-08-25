<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Acl\Voter;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Acl\Voter\MarketingListStateItemVoter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListStateItemInterface;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MarketingListStateItemVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ContactInformationFieldsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contactInformationFieldsProvider;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var MarketingListStateItemVoter */
    private $voter;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->contactInformationFieldsProvider = $this->createMock(ContactInformationFieldsProvider::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $container = TestContainerBuilder::create()
            ->add('oro_marketing_list.provider.contact_information_fields', $this->contactInformationFieldsProvider)
            ->getContainer($this);

        $this->voter = new MarketingListStateItemVoter($this->doctrineHelper, $container);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(
        ?int $identifier,
        ?string $className,
        mixed $object,
        int $expected,
        array $attributes,
        ?string $queryResult = null
    ) {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn($identifier);

        $repository = $this->createMock(ObjectRepository::class);

        $repository->expects($this->any())
            ->method('find')
            ->willReturnMap([
                [$identifier, $this->getItem()],
                [2, $object]
            ]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        if (is_object($object)) {
            $this->doctrineHelper->expects($this->any())
                ->method('getEntityClass')
                ->willReturn(get_class($object));
        }

        $this->contactInformationFieldsProvider->expects($this->any())
            ->method('getEntityTypedFields')
            ->willReturn(['email']);

        $this->contactInformationFieldsProvider->expects($this->any())
            ->method('getTypedFieldsValues')
            ->willReturn(['email']);

        $this->em->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->getQueryBuilder($queryResult));

        if ($className !== null) {
            $this->voter->setClassName($className);
        }

        $token = $this->createMock(TokenInterface::class);
        $this->assertEquals(
            $expected,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    public function attributesDataProvider(): array
    {
        return [
            [null, null, [], VoterInterface::ACCESS_ABSTAIN, []],
            [null, null, new \stdClass(), VoterInterface::ACCESS_ABSTAIN, []],
            [1, null, new \stdClass(), VoterInterface::ACCESS_ABSTAIN, ['VIEW']],
            [1, 'NotSupports', new \stdClass(), VoterInterface::ACCESS_ABSTAIN, ['DELETE']],
            [1, 'stdClass', new \stdClass(), VoterInterface::ACCESS_ABSTAIN, ['DELETE']],
            [1, 'stdClass', new \stdClass(), VoterInterface::ACCESS_ABSTAIN, ['DELETE'], '0'],
            [1, 'stdClass', new \stdClass(), VoterInterface::ACCESS_DENIED, ['DELETE'], '1'],
            [1, 'stdClass', new \stdClass(), VoterInterface::ACCESS_DENIED, ['DELETE'], '2'],
            [1, 'stdClass', null, VoterInterface::ACCESS_ABSTAIN, ['DELETE'], '2'],
        ];
    }

    private function getItem(): MarketingListStateItemInterface
    {
        $item = $this->createMock(MarketingListStateItemInterface::class);
        $marketingList = $this->createMock(MarketingList::class);

        $item->expects($this->any())
            ->method('getMarketingList')
            ->willReturn($marketingList);
        $item->expects($this->any())
            ->method('getEntityId')
            ->willReturn(2);

        $marketingList->expects($this->any())
            ->method('getEntity')
            ->willReturn('stdClass');

        return $item;
    }

    private function getQueryBuilder(mixed $queryResult): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('expr')
            ->willReturn(new Expr());
        $qb->expects($this->any())
            ->method('select')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('from')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setMaxResults')
            ->with(1)
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('setParameter')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);

        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $query->expects($this->any())
            ->method('getScalarResult')
            ->willReturn($queryResult);

        return $qb;
    }
}
