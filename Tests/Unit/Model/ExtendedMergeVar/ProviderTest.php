<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\ExtendedMergeVar;

use Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar\Provider;
use Oro\Bundle\MailChimpBundle\Model\ExtendedMergeVar\ProviderInterface;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;

class ProviderTest extends \PHPUnit\Framework\TestCase
{
    private MarketingList $marketingList;
    private Provider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->marketingList = new MarketingList();
        $this->provider = new Provider();
    }

    public function testProvideExtendedMergeVarsWithOutExternalProviders()
    {
        $extendedMergeVars = $this->provider->provideExtendedMergeVars($this->marketingList);
        $this->assertEquals([], $extendedMergeVars);
    }

    /**
     * @dataProvider extendedMergeVarsDataProvider
     */
    public function testProvideExtendedMergeVarsWithExternalProviders(
        array $externalProviderMergeVars,
        array $inheritedProviderMergeVars
    ) {
        $externalProvider = $this->createMock(ProviderInterface::class);
        $externalProvider->expects($this->once())
            ->method('provideExtendedMergeVars')
            ->willReturn($externalProviderMergeVars);

        $inheritedExternalProvider = $this->createMock(ProviderInterface::class);
        $externalProvider->expects($this->once())
            ->method('provideExtendedMergeVars')
            ->willReturn($inheritedProviderMergeVars);

        $this->provider->addProvider($externalProvider);
        $this->provider->addProvider($externalProvider);
        $this->provider->addProvider($inheritedExternalProvider);

        $actual = $this->provider->provideExtendedMergeVars($this->marketingList);

        $this->assertEquals($externalProviderMergeVars, $actual);
    }

    public function extendedMergeVarsDataProvider(): array
    {
        return [
            [
                [
                    [
                        'name' => 'e_dummy_name',
                        'label' => 'e_dummy_label'
                    ]
                ],
                [
                    [
                        'name' => 'inherited_name',
                        'label' => 'inherited_label'
                    ]
                ]
            ]
        ];
    }
}
