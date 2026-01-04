<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Oro\Bundle\ChannelBundle\Form\Type\CreateOrSelectInlineChannelAwareType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Mailchimp list selection form type.
 */
class MailchimpListSelectType extends AbstractType
{
    public const NAME = 'oro_mailchimp_list_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'mailchimp_lists',
                'grid_name' => 'oro_mailchimp_lists_grid',
                'configs' => [
                    'placeholder' => 'oro.mailchimp.emailcampaign.list.placeholder'
                ]
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return CreateOrSelectInlineChannelAwareType::class;
    }
}
