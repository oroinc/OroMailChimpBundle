<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Acl\Voter;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CampaignBundle\Entity\EmailCampaign;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\MailChimpBundle\Acl\Voter\EmailCampaignVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EmailCampaignVoterTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EmailCampaignVoter */
    private $voter;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->voter = new EmailCampaignVoter($this->doctrineHelper);
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testVote(array $attributes, EmailCampaign $emailCampaign, int $expected)
    {
        $object = $this->createMock(EmailCampaign::class);

        $this->doctrineHelper->expects(self::any())
            ->method('getSingleEntityIdentifier')
            ->with($object, false)
            ->willReturn(1);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->any())
            ->method('find')
            ->willReturn($emailCampaign);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $this->voter->setClassName(get_class($object));

        $token = $this->createMock(TokenInterface::class);
        $this->assertSame(
            $expected,
            $this->voter->vote($token, $object, $attributes)
        );
    }

    public function attributesDataProvider(): array
    {
        $emailCampaignNew = new EmailCampaign();
        $emailCampaignSent = new EmailCampaign();
        $emailCampaignSent->setSent(true);

        return [
            [['VIEW'], $emailCampaignNew, VoterInterface::ACCESS_ABSTAIN],
            [['CREATE'], $emailCampaignNew, VoterInterface::ACCESS_ABSTAIN],
            [['EDIT'], $emailCampaignNew, VoterInterface::ACCESS_ABSTAIN],
            [['DELETE'], $emailCampaignNew, VoterInterface::ACCESS_ABSTAIN],
            [['ASSIGN'], $emailCampaignNew, VoterInterface::ACCESS_ABSTAIN],
            [['VIEW'], $emailCampaignSent, VoterInterface::ACCESS_ABSTAIN],
            [['CREATE'], $emailCampaignSent, VoterInterface::ACCESS_ABSTAIN],
            [['EDIT'], $emailCampaignSent, VoterInterface::ACCESS_DENIED],
            [['DELETE'], $emailCampaignSent, VoterInterface::ACCESS_ABSTAIN],
            [['ASSIGN'], $emailCampaignSent, VoterInterface::ACCESS_ABSTAIN],
        ];
    }
}
