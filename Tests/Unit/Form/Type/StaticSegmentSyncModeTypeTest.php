<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Form\Type;

use Oro\Bundle\MailChimpBundle\Form\Type\StaticSegmentSyncModeType;
use Oro\Bundle\MailChimpBundle\Provider\StaticSegmentSyncModeChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StaticSegmentSyncModeTypeTest extends FormIntegrationTestCase
{
    private const CHOICES = ['scheduled' => 'scheduled', 'on_update' => 'on_update'];

    /** @var StaticSegmentSyncModeChoicesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $staticSegmentSyncModesProvider;

    /** @var StaticSegmentSyncModeType */
    private $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->staticSegmentSyncModesProvider = $this->createMock(StaticSegmentSyncModeChoicesProvider::class);

        $this->formType = new StaticSegmentSyncModeType($this->staticSegmentSyncModesProvider);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testSubmitForm()
    {
        $this->staticSegmentSyncModesProvider->expects(self::once())
            ->method('getTranslatedChoices')
            ->willReturn(self::CHOICES);

        $submittedData = 'scheduled';

        $form = $this->factory->create(StaticSegmentSyncModeType::class, null, []);

        $form->submit($submittedData);

        self::assertEquals($submittedData, $form->getData());
        self::assertEquals($submittedData, $form->getViewData());
        self::assertTrue($form->isSynchronized());
    }

    public function testConfigureOptions()
    {
        $this->staticSegmentSyncModesProvider->expects(self::once())
            ->method('getTranslatedChoices')
            ->willReturn(self::CHOICES);

        $resolver = new OptionsResolver();
        $this->formType->configureOptions($resolver);

        $actualOptions = $resolver->resolve();
        $expectedOptions = [
            'required' => true,
            'choices' => self::CHOICES,
            'translatable_options' => false,
        ];

        self::assertEquals($expectedOptions, $actualOptions);
    }

    public function testGetBlockPrefix()
    {
        self::assertEquals(StaticSegmentSyncModeType::NAME, $this->formType->getBlockPrefix());
    }

    public function testGetParent()
    {
        self::assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
