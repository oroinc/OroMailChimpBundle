<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Validator;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Entity\SubscribersList;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use Oro\Bundle\MailChimpBundle\Validator\Constraints\UniqueStaticSegmentNameConstraint;
use Oro\Bundle\MailChimpBundle\Validator\UniqueStaticSegmentNameValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueStaticSegmentNameValidatorTest extends ConstraintValidatorTestCase
{
    /** @var MailChimpTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    #[\Override]
    protected function setUp(): void
    {
        $this->transport = $this->createMock(MailChimpTransport::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): UniqueStaticSegmentNameValidator
    {
        return new UniqueStaticSegmentNameValidator($this->transport);
    }

    public function testValidateIncorrectInstance()
    {
        $value = new \stdClass();

        $this->transport->expects($this->never())
            ->method($this->anything());

        $constraint = new UniqueStaticSegmentNameConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateHasOrigin()
    {
        $value = $this->createMock(StaticSegment::class);
        $value->expects($this->once())
            ->method('getOriginId')
            ->willReturn('123');

        $this->transport->expects($this->never())
            ->method($this->anything());

        $constraint = new UniqueStaticSegmentNameConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateCorrect()
    {
        $transport = $this->createMock(Transport::class);

        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $list = $this->createMock(SubscribersList::class);

        $value = $this->createMock(StaticSegment::class);
        $value->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $value->expects($this->once())
            ->method('getName')
            ->willReturn('other');
        $value->expects($this->once())
            ->method('getSubscribersList')
            ->willReturn($list);

        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);
        $this->transport->expects($this->once())
            ->method('getListStaticSegments')
            ->with($list)
            ->willReturn([['name' => 'some']]);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());

        $constraint = new UniqueStaticSegmentNameConstraint();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateIncorrect()
    {
        $name = 'other';

        $transport = $this->createMock(Transport::class);

        $channel = $this->createMock(Channel::class);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);

        $list = $this->createMock(SubscribersList::class);

        $value = $this->createMock(StaticSegment::class);
        $value->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $value->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $value->expects($this->once())
            ->method('getSubscribersList')
            ->willReturn($list);

        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);
        $this->transport->expects($this->once())
            ->method('getListStaticSegments')
            ->with($list)
            ->willReturn([['name' => $name]]);

        $constraint = new UniqueStaticSegmentNameConstraint();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->atPath('property.path.name')
            ->assertRaised();
    }
}
