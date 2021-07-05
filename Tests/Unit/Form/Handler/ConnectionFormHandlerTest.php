<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Form\Handler\ConnectionFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ConnectionFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    private $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $registry;

    /** @var ConnectionFormHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->handler = new ConnectionFormHandler($this->request, $this->registry, $this->form);
    }

    public function testProcessGet()
    {
        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $staticSegmentManager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroMailChimpBundle:StaticSegment');

        $staticSegmentManager->expects($this->never())
            ->method($this->anything());
        $this->request->expects($this->once())
            ->method('isMethod')
            ->with('POST')
            ->willReturn(false);

        $this->assertNull($this->handler->process($staticSegment));
    }

    public function testProcessNewEntity()
    {
        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $staticSegmentManager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroMailChimpBundle:StaticSegment')
            ->willReturn($staticSegmentManager);

        $this->assertSubmit();
        $this->assertSave($staticSegmentManager, $staticSegment);

        $this->assertSame($staticSegment, $this->handler->process($staticSegment));
    }

    public function testProcessExistingEntitySameList()
    {
        $subscribersList = $this->createMock(SubscribersList::class);
        $subscribersList->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $staticSegment->expects($this->any())
            ->method('getSubscribersList')
            ->willReturn($subscribersList);
        $staticSegment->expects($this->once())
            ->method('setSyncStatus')
            ->with(StaticSegment::STATUS_SCHEDULED);

        $staticSegmentManager = $this->createMock(ObjectManager::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroMailChimpBundle:StaticSegment')
            ->willReturn($staticSegmentManager);

        $this->assertSubmit();
        $this->assertSave($staticSegmentManager, $staticSegment);

        $this->assertSame($staticSegment, $this->handler->process($staticSegment));
    }

    /**
     * @dataProvider campaignDataProvider
     */
    public function testProcessExistingEntityListChange(?Campaign $campaign)
    {
        $subscribersList = $this->createMock(SubscribersList::class);
        $subscribersList->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        $staticSegment = $this->getMockBuilder(StaticSegment::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();
        $staticSegment->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $staticSegment->setSubscribersList($subscribersList);

        $staticSegmentManager = $this->createMock(ObjectManager::class);
        $staticSegmentManager->expects(!$campaign ? $this->once() : $this->never())
            ->method('remove');

        $campaignManager = $this->createMock(ObjectManager::class);
        $campaignRepository = $this->createMock(ObjectRepository::class);
        $campaignRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($campaign);
        $campaignManager->expects($this->any())
            ->method('getRepository')
            ->with('OroMailChimpBundle:Campaign')
            ->willReturn($campaignRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnMap([
                ['OroMailChimpBundle:StaticSegment', $staticSegmentManager],
                ['OroMailChimpBundle:Campaign', $campaignManager]
            ]);

        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(true);

        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request)
            ->willReturnCallback(function () use ($staticSegment) {
                $subscribersList = $this->createMock(SubscribersList::class);
                $subscribersList->expects($this->any())
                    ->method('getId')
                    ->willReturn(1);
                $staticSegment->setSubscribersList($subscribersList);
            });
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $staticSegmentManager->expects($this->once())
            ->method('persist');
        $staticSegmentManager->expects($this->once())
            ->method('flush');

        $actualSegment = $this->handler->process($staticSegment);
        $this->assertInstanceOf(StaticSegment::class, $actualSegment);
        $this->assertNull($actualSegment->getId());
        $this->assertEquals(StaticSegment::STATUS_NOT_SYNCED, $actualSegment->getSyncStatus());
    }

    public function campaignDataProvider(): array
    {
        return [
            [new Campaign()],
            [null]
        ];
    }

    private function assertSubmit()
    {
        $this->request->expects($this->once())
            ->method('isMethod')
            ->with(Request::METHOD_POST)
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);
    }

    /**
     * @param ObjectManager|\PHPUnit\Framework\MockObject\MockObject $staticSegmentManager
     * @param StaticSegment $staticSegment
     */
    private function assertSave(ObjectManager $staticSegmentManager, StaticSegment $staticSegment)
    {
        $staticSegmentManager->expects($this->once())
            ->method('persist')
            ->with($staticSegment);
        $staticSegmentManager->expects($this->once())
            ->method('flush');
    }
}
