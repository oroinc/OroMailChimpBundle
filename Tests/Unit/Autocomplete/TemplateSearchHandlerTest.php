<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Autocomplete;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\MailChimpBundle\Autocomplete\TemplateSearchHandler;
use Oro\Bundle\MailChimpBundle\Entity\Template;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;

class TemplateSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const TEST_ENTITY_CLASS = 'FooEntityClass';
    private const ID = 'id';

    /** @var array */
    private $testProperties = ['name', 'email'];

    /** @var TemplateSearchHandler */
    private $searchHandler;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $queryBuilder;

    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var Expr|\PHPUnit\Framework\MockObject\MockObject */
    private $expr;

    protected function setUp(): void
    {
        $this->query = $this->createMock(AbstractQuery::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->expr = $this->createMock(Expr::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->searchHandler = new TemplateSearchHandler(self::TEST_ENTITY_CLASS, $this->testProperties);
        $this->searchHandler->setPropertyAccessor(PropertyAccess::createPropertyAccessor());
        $this->searchHandler->setAclHelper($this->aclHelper);
    }

    private function setUpExpects()
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getSingleIdentifierFieldName')
            ->willReturn(self::ID);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($metadata);

        $this->entityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturnArgument(0);
        $this->entityRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);
        $this->entityManager->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::TEST_ENTITY_CLASS)
            ->willReturn($this->entityManager);

        $this->searchHandler->initDoctrinePropertiesByManagerRegistry($this->managerRegistry);
    }

    public function testSearchEntitiesQueryBuilder()
    {
        $this->setUpExpects();
        $this->setSearchExpects();

        $search = 'test;1';
        $firstResult = 1;
        $maxResults = 10;
        $result = ReflectionUtil::callMethod(
            $this->searchHandler,
            'searchEntities',
            [$search, $firstResult, $maxResults]
        );
        $this->assertEmpty($result);
    }

    public function testCheckDependenciesInjectedFail()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Search handler is not fully configured');

        $this->searchHandler->search('', 1, 1);
    }

    public function testFindByIdArrayArgumentsInRequest()
    {
        $this->setUpExpects();

        $result = ReflectionUtil::callMethod($this->searchHandler, 'findById', ['1;1']);

        $this->assertEquals([['id' => 1, 'channel' => 1]], $result);
    }

    /**
     * @dataProvider templateConvertDataProvider
     */
    public function testConvertItemsWithCategory(array $expected)
    {
        $this->setUpExpects();

        $templateOne = new Template();
        $templateOne->setCategory($expected[0]['name'])->setName($expected[0]['children'][0]['name']);
        $templateTwo = new Template();
        $templateTwo->setCategory($expected[1]['name'])->setName($expected[1]['children'][0]['name']);
        $templates = [$templateOne, $templateTwo];
        $result = ReflectionUtil::callMethod($this->searchHandler, 'convertItems', [$templates]);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider templateConvertDataProvider
     */
    public function testConvertItemsWithType(array $expected)
    {
        $this->setUpExpects();

        $templateOne = new Template();
        $templateOne->setType($expected[0]['name'])->setName($expected[0]['children'][0]['name']);
        $templateTwo = new Template();
        $templateTwo->setType($expected[1]['name'])->setName($expected[1]['children'][0]['name']);
        $templates = [$templateOne, $templateTwo];
        $result = ReflectionUtil::callMethod($this->searchHandler, 'convertItems', [$templates]);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider templateConvertDataProvider
     */
    public function testSearchEntitiesValidResult(array $expected)
    {
        $this->setUpExpects();
        $this->setSearchExpects();

        $templateOne = new Template();
        $templateOne->setCategory($expected[0]['name'])->setName($expected[0]['children'][0]['name']);
        $templateTwo = new Template();
        $templateTwo->setType($expected[1]['name'])->setName($expected[1]['children'][0]['name']);
        $templateTree = new Template();
        $templateTree->setType($expected[1]['name'] . '3')->setName($expected[1]['children'][0]['name']);
        $templateFour = new Template();
        $templateFour->setType($expected[1]['name'] . '4')->setName($expected[1]['children'][0]['name']);
        $templates = [$templateOne, $templateTwo, $templateTree, $templateFour];
        $this->query->expects($this->once())
            ->method('getResult')
            ->willReturn($templates);

        $search = 'test;1';
        $firstResult = 1;
        $maxResults = 2;
        $result = $this->searchHandler->search($search, $firstResult, $maxResults);
        $this->assertFalse($result['more']);
        $this->assertCount(4, $result['results']);
    }

    /**
     * @dataProvider templateConvertDataProvider
     */
    public function testSearchEntitiesByIdValidResult(array $expected)
    {
        $this->setUpExpects();
        $templateOne = new Template();
        $templateOne->setCategory($expected[0]['name'])->setName($expected[0]['children'][0]['name']);
        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($templateOne);
        $search = 'test;1';
        $firstResult = 1;
        $maxResults = 2;
        $result = $this->searchHandler->search($search, $firstResult, $maxResults, true);
        $this->assertFalse($result['more']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('test', $result['results'][0]['id']);
    }

    public function templateConvertDataProvider(): array
    {
        return
            [
                [
                    [
                        [
                            'name'     => 'C1',
                            'children' => [
                                [
                                    'id'    => null,
                                    'name'  => 'Name',
                                    'email' => null,
                                ]
                            ]
                        ],
                        [
                            'name'     => 'C2',
                            'children' => [
                                [
                                    'id'    => null,
                                    'name'  => null,
                                    'email' => null,
                                ]
                            ]
                        ]
                    ]
                ]
            ];
    }

    private function setSearchExpects()
    {
        $this->entityRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->exactly(2))
            ->method('andWhere')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->exactly(2))
            ->method('addOrderBy')
            ->willReturnSelf();
        $this->queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($this->expr);
        $this->queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($this->query);
        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($this->identicalTo($this->queryBuilder))
            ->willReturn($this->query);
    }
}
