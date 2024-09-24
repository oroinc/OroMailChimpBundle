<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\EventListener\NotifyMailChimpListener;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Event\UpdateMarketingListEvent;
use Oro\Component\Testing\ReflectionUtil;

class NotifyMailChimpListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var NotifyMailChimpListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(EntityRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        $this->listener = new NotifyMailChimpListener($doctrineHelper);
    }

    public function testUpdateStaticSegmentSyncStatus()
    {
        $marketingList = new MarketingList();
        ReflectionUtil::setId($marketingList, 1);

        $staticSegment = new StaticSegment();
        ReflectionUtil::setId($marketingList, 1);
        $staticSegment->setSegmentMembers(new ArrayCollection());

        $event = new UpdateMarketingListEvent();
        $event->addMarketingList($marketingList);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['marketingList' => $marketingList])
            ->willReturn([$staticSegment]);

        $this->listener->onMarketingListChange($event);

        $this->assertSame(StaticSegment::STATUS_SCHEDULED_BY_CHANGE, $staticSegment->getSyncStatus());
    }
}
