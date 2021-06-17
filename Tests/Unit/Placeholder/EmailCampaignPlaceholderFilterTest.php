<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Placeholder;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\MailChimpBundle\Entity\Campaign;
use Oro\Bundle\MailChimpBundle\Placeholder\EmailCampaignPlaceholderFilter;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

class EmailCampaignPlaceholderFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var EntityRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $entityRepository;

    /** @var EmailCampaignPlaceholderFilter */
    private $placeholderFilter;

    protected function setUp(): void
    {
        $this->entityRepository = $this->createMock(EntityRepository::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);

        $this->placeholderFilter = new EmailCampaignPlaceholderFilter($this->managerRegistry);
    }

    public function testIsNotApplicableEntityOnEmailCampaign()
    {
        $entity = $this->createMock(MarketingList::class);
        $this->assertFalse($this->placeholderFilter->isApplicableOnEmailCampaign($entity));
    }

    /**
     * @dataProvider staticCampaignProvider
     */
    public function testIsApplicableOnEmailCampaign(?EmailCampaign $emailCampaign, ?Campaign $campaign, bool $expected)
    {
        $this->entityRepository->expects($this->any())
            ->method('findOneBy')
            ->willReturn($campaign);
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($this->entityManager);
        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->entityRepository);

        $this->assertEquals(
            $expected,
            $this->placeholderFilter->isApplicableOnEmailCampaign($emailCampaign)
        );
    }

    public function staticCampaignProvider(): array
    {
        $emailCampaign = new EmailCampaign();
        $mailchimpEmailCampaign = new EmailCampaign();
        $mailchimpEmailCampaign->setTransport('mailchimp');
        return [
            [null, null, false],
            [null, new Campaign(), false],
            [$emailCampaign, null, false],
            [$emailCampaign, new Campaign(), false],
            [$mailchimpEmailCampaign, null, false],
            [$mailchimpEmailCampaign, new Campaign(), true],
        ];
    }
}
