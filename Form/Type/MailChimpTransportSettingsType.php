<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Oro\Bundle\CampaignBundle\Form\Type\AbstractTransportSettingsType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Mailchimp transport settings form type.
 */
class MailChimpTransportSettingsType extends AbstractTransportSettingsType
{
    public const NAME = 'oro_mailchimp_email_transport_settings';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'channel',
                MailChimpIntegrationSelectType::class,
                [
                    'label' => 'oro.mailchimp.emailcampaign.integration.label',
                    'required' => true
                ]
            )
            /*
            ->add(
                'template',
                'oro_mailchimp_template_select',
                [
                    'label' => 'oro.mailchimp.emailcampaign.template.label',
                    'required' => true,
                    'channel_field' => 'channel'
                ]
            )*/;

        parent::buildForm($builder, $options);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Oro\Bundle\MailChimpBundle\Entity\MailChimpTransportSettings'
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
}
