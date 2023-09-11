<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Mailchimp API key form type. It has custom theming bound to perform connection check.
 */
class ApiKeyType extends AbstractType
{
    const NAME = 'oro_mailchimp_api_key_type';

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
