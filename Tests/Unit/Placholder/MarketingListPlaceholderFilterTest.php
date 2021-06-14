<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Placeholder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Placeholder\MarketingListPlaceholderFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

class MarketingListPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var MarketingListPlaceholderFilter */
    private $placeholderFilter;

    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->placeholderFilter = new MarketingListPlaceholderFilter($this->managerRegistry);
    }

    public function testIsNotApplicableEntityOnMarketingList()
    {
        $entity = $this->createMock(EmailCampaign::class);
        $this->placeholderFilter->isApplicableOnMarketingList($entity);

        $this->assertFalse($this->placeholderFilter->isApplicableOnMarketingList($entity));
    }

    /**
     * @dataProvider staticSegmentDataProvider
     */
    public function testIsApplicableOnMarketingList(?StaticSegment $staticSegment, bool $expected)
    {
        $this->entityRepository->expects($this->once())
            ->method('findOneBy')
            ->willReturn($staticSegment);
        $this->managerRegistry->expects($this->once())
            ->method('getManager')
            ->willReturn($this->entityManager);
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        $entity = new MarketingList();
        $this->assertEquals($expected, $this->placeholderFilter->isApplicableOnMarketingList($entity));
    }

    public function staticSegmentDataProvider(): array
    {
        return [
            [null, false],
            [new StaticSegment(), true],
        ];
    }
}
