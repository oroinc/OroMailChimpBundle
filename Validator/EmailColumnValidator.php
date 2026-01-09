<?php

namespace Oro\Bundle\MailChimpBundle\Validator;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Provider\ContactInformationFieldsProvider;
use Oro\Bundle\MarketingListBundle\Validator\Constraints\ContactInformationColumn;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Email column(field) validator.
 */
class EmailColumnValidator extends ConstraintValidator
{
    /**
     * @var ConstraintValidator
     */
    protected $fieldInformationValidator;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    public function __construct(ConstraintValidator $fieldInformationValidator, ManagerRegistry $registry)
    {
        $this->fieldInformationValidator = $fieldInformationValidator;
        $this->registry = $registry;
    }

    #[\Override]
    public function initialize(ExecutionContextInterface $context): void
    {
        $this->fieldInformationValidator->initialize($context);
    }

    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof MarketingList && !$value->isManual() && $this->isConnectedToMailChimp($value)) {
            $fieldValidatorConstraint = new ContactInformationColumn();
            $fieldValidatorConstraint->type = ContactInformationFieldsProvider::CONTACT_INFORMATION_SCOPE_EMAIL;
            $this->fieldInformationValidator->validate($value->getSegment(), $fieldValidatorConstraint);
        }
    }

    /**
     * @param MarketingList $marketingList
     * @return bool
     */
    protected function isConnectedToMailChimp(MarketingList $marketingList)
    {
        return (bool)$this->registry->getRepository(StaticSegment::class)
            ->findOneBy(['marketingList' => $marketingList]);
    }
}
