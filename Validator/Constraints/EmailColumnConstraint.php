<?php

namespace Oro\Bundle\MailChimpBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Email column(field) entity wide constraint.
 */
class EmailColumnConstraint extends Constraint
{
    #[\Override]
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }

    #[\Override]
    public function validatedBy(): string
    {
        return 'oro_mailchimp.validator.email_column';
    }
}
