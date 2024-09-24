<?php

namespace Oro\Bundle\MailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Unique static segment name constraint.
 */
class UniqueStaticSegmentNameConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.mailchimp.unique_static_segment_name.message';

    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_mailchimp.validator.unique_static_segment_name';
    }
}
