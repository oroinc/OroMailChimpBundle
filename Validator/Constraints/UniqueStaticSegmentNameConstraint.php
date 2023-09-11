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

    /**
     * {@inheritdoc}
     */
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy(): string
    {
        return 'oro_mailchimp.validator.unique_static_segment_name';
    }
}
