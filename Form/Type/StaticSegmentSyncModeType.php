<?php

namespace Oro\Bundle\MailChimpBundle\Form\Type;

use Oro\Bundle\MailChimpBundle\Provider\StaticSegmentSyncModeChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Mailchimp static segment synchronization mode form type.
 */
class StaticSegmentSyncModeType extends AbstractType
{
    public const NAME = 'oro_mailchimp_static_segment_sync_mode';

    /**
     * @var StaticSegmentSyncModeChoicesProvider
     */
    private $staticSegmentSyncModesProvider;

    public function __construct(StaticSegmentSyncModeChoicesProvider $staticSegmentSyncModesProvider)
    {
        $this->staticSegmentSyncModesProvider = $staticSegmentSyncModesProvider;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'required' => true,
                'choices' => $this->staticSegmentSyncModesProvider->getTranslatedChoices(),
                // We expect that staticSegmentSyncModesProvider returns already translated choices list, so we disable
                // the translation in template.
                'translatable_options' => false,
            ]
        );
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
