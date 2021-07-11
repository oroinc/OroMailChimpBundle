<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Validator;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\MailChimpBundle\Validator\EmailColumnValidator;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\MarketingListBundle\Validator\Constraints\ContactInformationColumnConstraint;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailColumnValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConstraintValidator|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldInformationValidator;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EmailColumnValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->fieldInformationValidator = $this->createMock(ConstraintValidator::class);
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->validator = new EmailColumnValidator($this->fieldInformationValidator, $this->registry);
    }

    public function testInitialize()
    {
        $context = $this->getMockForAbstractClass(ExecutionContextInterface::class);

        $this->fieldInformationValidator->expects($this->once())
            ->method('initialize')
            ->with($context);

        $this->validator->initialize($context);
    }

    public function testValidateForNotMarketingList()
    {
        $this->fieldInformationValidator->expects($this->never())
            ->method('validate');

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateValidForConnectedManualMarketingList()
    {
        $marketingList = $this->createMock(MarketingList::class);
        $marketingList->expects($this->any())
            ->method('isManual')
            ->willReturn(true);

        $this->fieldInformationValidator->expects($this->never())
            ->method('validate');

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate($marketingList, $constraint);
    }

    public function testValidateNotConnected()
    {
        $marketingList = $this->createMock(MarketingList::class);
        $marketingList->expects($this->once())
            ->method('isManual')
            ->willReturn(false);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['marketingList' => $marketingList]);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroMailChimpBundle:StaticSegment')
            ->willReturn($repository);

        $this->fieldInformationValidator->expects($this->never())
            ->method('validate');

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate($marketingList, $constraint);
    }

    public function testValidate()
    {
        $segment = $this->createMock(Segment::class);

        $marketingList = $this->createMock(MarketingList::class);
        $marketingList->expects($this->once())
            ->method('isManual')
            ->willReturn(false);
        $marketingList->expects($this->once())
            ->method('getSegment')
            ->willReturn($segment);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['marketingList' => $marketingList])
            ->willReturn(new \stdClass());
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroMailChimpBundle:StaticSegment')
            ->willReturn($repository);

        $fieldValidatorConstraint = new ContactInformationColumnConstraint();
        $fieldValidatorConstraint->type = ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL;

        $this->fieldInformationValidator->expects($this->once())
            ->method('validate')
            ->with($segment, $fieldValidatorConstraint);

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate($marketingList, $constraint);
    }
}
