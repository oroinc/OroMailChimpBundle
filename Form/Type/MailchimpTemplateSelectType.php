<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Mailchimp template select form type.
 */
class MailchimpTemplateSelectType extends AbstractType
{
    const NAME = 'oro_mailchimp_template_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'mailchimp_templates',
                'grid_name' => 'oro_mailchimp_templates_grid',
                'configs' => [
                    'placeholder' => 'oro.mailchimp.emailcampaign.template.placeholder'
                ]
            ]
        );
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

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return CreateOrSelectInlineChannelAwareType::class;
    }
}
