<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Marketing list
 */
class MarketingListConnectionType extends AbstractType
{
    public const NAME = 'oro_mailchimp_marketing_list_connection';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'name',
                TextType::class,
                [
                    'label' => 'oro.mailchimp.connection.segment_name',
                    'required' => true
                ]
            )
            ->add(
                'channel',
                MailChimpIntegrationSelectType::class,
                [
                    'label' => 'oro.mailchimp.emailcampaign.integration.label',
                    'required' => true
                ]
            )
            ->add(
                'subscribersList',
                MailchimpListSelectType::class,
                [
                    'label' => 'oro.mailchimp.subscriberslist.entity_label',
                    'required' => true,
                    'channel_field' => 'channel'
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StaticSegment::class,
        ]);
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
