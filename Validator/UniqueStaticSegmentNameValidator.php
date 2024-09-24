<?php

namespace Oro\Bundle\MailChimpBundle\Validator;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MailChimpBundle\Provider\Transport\MailChimpTransport;
use Oro\Bundle\MailChimpBundle\Validator\Constraints\UniqueStaticSegmentNameConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Unique static segment name validator.
 */
class UniqueStaticSegmentNameValidator extends ConstraintValidator
{
    /**
     * @var TransportInterface|MailChimpTransport
     */
    protected $transport;

    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * @param StaticSegment $value
     * @param UniqueStaticSegmentNameConstraint|Constraint $constraint
     */
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof StaticSegment && !$value->getOriginId()) {
            $this->transport->init($value->getChannel()->getTransport());

            $segments = $this->transport->getListStaticSegments($value->getSubscribersList());
            foreach ($segments as $segment) {
                if ($segment['name'] === $value->getName()) {
                    $this->context->buildViolation($constraint->message)
                        ->atPath('name')
                        ->addViolation();
                    break;
                }
            }
        }
    }
}
