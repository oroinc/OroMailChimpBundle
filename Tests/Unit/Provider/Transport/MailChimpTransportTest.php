<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransport as MailChimpTransportEntity;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\Repository\SubscribersListRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Exception\RequiredOptionException;
use Oro\Bundle\MailChimpBundle\Form\Type\IntegrationSettingsType;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\CampaignIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\ListsMembersSubordinateIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClientFactory;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\NullLogger;

class MailChimpTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var MailChimpClientFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $clientFactory;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var MailChimpTransport */
    private $transport;

    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(MailChimpClientFactory::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->transport = new MailChimpTransport($this->clientFactory, $this->managerRegistry);
        $this->transport->setLogger(new NullLogger());
    }

    public function testGetSettingsEntityFQCN()
    {
        self::assertInstanceOf($this->transport->getSettingsEntityFQCN(), new MailChimpTransportEntity());
    }

    public function testGetLabel()
    {
        self::assertEquals('oro.mailchimp.integration_transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        self::assertEquals(IntegrationSettingsType::class, $this->transport->getSettingsFormType());
    }

    public function testInitWorks()
    {
        $client = $this->initTransport();

        self::assertEquals($client, ReflectionUtil::getPropertyValue($this->transport, 'client'));
    }

    private function initTransport(): \PHPUnit\Framework\MockObject\MockObject
    {
        $apiKey = md5('test_api_key');

        $transportEntity = new MailChimpTransportEntity();
        $transportEntity->setApiKey($apiKey);

        $client = $this->createMock(MailChimpClient::class);

        $this->clientFactory->expects(self::once())
            ->method('create')
            ->with($apiKey)
            ->willReturn($client);

        $this->transport->init($transportEntity);

        return $client;
    }

    public function testInitFails()
    {
        $this->expectException(RequiredOptionException::class);
        $this->expectExceptionMessage('Option "apiKey" is required');

        $transportEntity = new MailChimpTransportEntity();

        $this->clientFactory->expects(self::never())
            ->method(self::anything());
        $this->transport->init($transportEntity);
    }

    /**
     * @dataProvider getCampaignsDataProvider
     */
    public function testGetCampaigns(?string $status, ?bool $usesSegment, array $expectedFilters)
    {
        $staticSegmentRepository = $this->createMock(StaticSegmentRepository::class);

        $this->managerRegistry->expects(self::once())
            ->method('getRepository')
            ->willReturn($staticSegmentRepository);

        $staticSegmentRepository->expects(self::once())
            ->method('getStaticSegments')
            ->willReturn([$this->getStaticSegment()]);

        $channel = $this->createMock(Channel::class);

        $this->initTransport();
        $result = $this->transport->getCampaigns($channel, $status, $usesSegment);

        self::assertInstanceOf(CampaignIterator::class, $result);
        self::assertSame($expectedFilters, $result->getFilters());
    }

    private function getStaticSegment(): StaticSegment
    {
        $subscribersList = $this->createMock(SubscribersList::class);
        $subscribersList->expects(self::once())
            ->method('getOriginId')
            ->willReturn('originId');

        $staticSegment = $this->createMock(StaticSegment::class);
        $staticSegment->expects(self::once())
            ->method('getSubscribersList')
            ->willReturn($subscribersList);

        return $staticSegment;
    }

    public function getCampaignsDataProvider(): array
    {
        return [
            [
                'status' => null,
                'usesSegment' => null,
                'filters' => [
                    'list_ids' => ['originId'],
                    'exact' => false,
                ],
            ],
            [
                'status' => Campaign::STATUS_SENT,
                'usesSegment' => null,
                'filters' => [
                    'status' => Campaign::STATUS_SENT,
                    'list_ids' => ['originId'],
                    'exact' => false,
                ],
            ],
            [
                'status' => null,
                'usesSegment' => true,
                'filters' => [
                    'uses_segment' => true,
                    'list_ids' => ['originId'],
                    'exact' => false,
                ],
            ],
            [
                'status' => Campaign::STATUS_SENT,
                'usesSegment' => true,
                'filters' => [
                    'status' => Campaign::STATUS_SENT,
                    'uses_segment' => true,
                    'list_ids' => ['originId'],
                    'exact' => false,
                ],
            ],
        ];
    }

    public function testGetMembersToSync()
    {
        $subscribersList = $this->createMock(SubscribersList::class);
        $subscribersList
            ->expects($this->once())
            ->method('getOriginId')
            ->willReturn('origin_id');
        $subscribersLists = new \ArrayIterator([$subscribersList]);

        $subscribersListRepository = $this->createMock(SubscribersListRepository::class);
        $subscribersListRepository
            ->expects($this->once())
            ->method('getUsedSubscribersListIterator')
            ->willReturn($subscribersLists);

        $this->managerRegistry
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($subscribersListRepository);

        $since = new \DateTime('2015-02-15 21:00:01', new \DateTimeZone('Europe/Kiev'));
        $channel = $this->createMock(Channel::class);

        $client = $this->initTransport();
        $client
            ->expects($this->once())
            ->method('getListMembers')
            ->willReturn(['members' => [['id' => 1], ['id' => 2]], 'total_items' => 2]);
        $result = $this->transport->getMembersToSync($channel, $since);

        $this->assertInstanceOf(ListsMembersSubordinateIterator::class, $result);
        $this->assertEquals([['id' => 1], ['id' => 2]], iterator_to_array($result));
    }
}
