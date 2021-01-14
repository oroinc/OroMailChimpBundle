<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider\Transport;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Entity\MailChimpTransport as MailChimpTransportEntity;
use Oro\Bundle\MailChimpBundle\Entity\Member;
use Oro\Bundle\MailChimpBundle\Entity\Repository\StaticSegmentRepository;
use Oro\Bundle\MailChimpBundle\Entity\Repository\SubscribersListRepository;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Exception\RequiredOptionException;
use Oro\Bundle\MailChimpBundle\Form\Type\IntegrationSettingsType;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\CampaignIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\Iterator\MemberIterator;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClient;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpClientFactory;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class MailChimpTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|MailChimpClientFactory */
    protected $clientFactory;

    /** @var MockObject|ManagerRegistry */
    protected $managerRegistry;

    /** @var MailChimpTransport */
    protected $transport;

    protected function setUp(): void
    {
        $this->clientFactory = $this->getMockBuilder(MailChimpClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->transport = new class($this->clientFactory, $this->managerRegistry) extends MailChimpTransport {
            public function xgetClient(): MailChimpClient
            {
                return $this->client;
            }
        };
        $this->transport->setLogger(new NullLogger());
    }

    public function testGetSettingsEntityFQCN()
    {
        static::assertInstanceOf($this->transport->getSettingsEntityFQCN(), new MailChimpTransportEntity());
    }

    public function testGetLabel()
    {
        static::assertEquals('oro.mailchimp.integration_transport.label', $this->transport->getLabel());
    }

    public function testGetSettingsFormType()
    {
        static::assertEquals(
            IntegrationSettingsType::class,
            $this->transport->getSettingsFormType()
        );
    }

    public function testInitWorks()
    {
        $client = $this->initTransport();

        static::assertEquals($client, $this->transport->xgetClient());
    }

    /**
     * @return MockObject
     */
    protected function initTransport()
    {
        $apiKey = md5(rand());

        $transportEntity = new MailChimpTransportEntity();
        $transportEntity->setApiKey($apiKey);

        $client = $this->getMockBuilder(MailChimpClient::class)->disableOriginalConstructor()->getMock();

        $this->clientFactory->expects(static::once())
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

        $this->clientFactory->expects(static::never())->method(static::anything());
        $this->transport->init($transportEntity);
    }

    /**
     * @dataProvider getCampaignsDataProvider
     *
     * @param string|null $status
     * @param bool|null $usesSegment
     * @param array $expectedFilters
     */
    public function testGetCampaigns($status, $usesSegment, array $expectedFilters)
    {
        $staticSegmentRepository = $this->getMockBuilder(StaticSegmentRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry
            ->expects(static::once())
            ->method('getRepository')
            ->willReturn($staticSegmentRepository);

        $staticSegmentRepository
            ->expects(static::once())
            ->method('getStaticSegments')
            ->willReturn([$this->getStaticSegmentMock()]);

        /** @var Channel $channel */
        $channel = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();

        $this->initTransport();
        $result = $this->transport->getCampaigns($channel, $status, $usesSegment);

        static::assertInstanceOf(CampaignIterator::class, $result);
        static::assertSame($expectedFilters, $result->getFilters());
    }

    /**
     * @return MockObject
     */
    protected function getStaticSegmentMock()
    {
        $staticSegmentMock = $this->getMockBuilder(StaticSegment::class)->disableOriginalConstructor()->getMock();

        $subscribersList = $this->getMockBuilder(SubscribersList::class)->disableOriginalConstructor()->getMock();

        $staticSegmentMock
            ->expects(static::once())
            ->method('getSubscribersList')
            ->willReturn($subscribersList);

        $subscribersList
            ->expects(static::once())
            ->method('getOriginId')
            ->willReturn('originId');

        return $staticSegmentMock;
    }

    /**
     * @return array
     */
    public function getCampaignsDataProvider()
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
        $subscribersListRepository = $this->getMockBuilder(SubscribersListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry->expects(static::once())
            ->method('getRepository')
            ->willReturn($subscribersListRepository);

        $subscribersList = $this->createMock(SubscribersList::class);
        $subscribersLists = new \ArrayIterator([$subscribersList]);

        $subscribersListRepository->expects(static::once())
            ->method('getUsedSubscribersListIterator')
            ->willReturn($subscribersLists);

        $since = new \DateTime('2015-02-15 21:00:01', new \DateTimeZone('Europe/Kiev'));

        /** @var Channel $channel */
        $channel = $this->getMockBuilder(Channel::class)->disableOriginalConstructor()->getMock();

        $client = $this->initTransport();
        $result = $this->transport->getMembersToSync($channel, $since);

        static::assertInstanceOf(MemberIterator::class, $result);
        static::assertSame($client, $result->getClient());
        static::assertSame($subscribersLists, $result->getMainIterator());
        static::assertEquals(
            [
                'status' => [Member::STATUS_SUBSCRIBED, Member::STATUS_UNSUBSCRIBED, Member::STATUS_CLEANED],
                'since' => '2015-02-15 19:00:00',
            ],
            $result->getParameters()
        );
    }
}
