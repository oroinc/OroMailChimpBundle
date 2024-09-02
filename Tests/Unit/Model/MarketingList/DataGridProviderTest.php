<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Model\MarketingList;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\MailChimpBundle\Model\MarketingList\DataGridProvider;
use Oro\Bundle\MarketingListBundle\Datagrid\ConfigurationProvider;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\MarketingListBundle\Entity\MarketingListType;
use Oro\Bundle\MarketingListBundle\Provider\MarketingListProvider;

class DataGridProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dataGridManager;

    /** @var DataGridProvider */
    private $dataGridProvider;

    protected function setUp(): void
    {
        $this->dataGridManager = $this->createMock(ManagerInterface::class);

        $this->dataGridProvider = new DataGridProvider($this->dataGridManager);
    }

    /**
     * @dataProvider marketingListTypeDataProvider
     */
    public function testGetDataGridColumns(string $type)
    {
        $marketingList = $this->createMock(MarketingList::class);
        $marketingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $marketingList->expects($this->any())
            ->method('isManual')
            ->willReturn($type === MarketingListType::TYPE_MANUAL);

        $config = $this->createMock(DatagridConfiguration::class);

        $dataGrid = $this->createMock(DatagridInterface::class);
        $dataGrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->dataGridManager->expects($this->atLeastOnce())
            ->method('getDatagrid')
            ->with(
                ConfigurationProvider::GRID_PREFIX . $marketingList->getId(),
                $this->logicalAnd(
                    $this->arrayHasKey('grid-mixin'),
                    $this->callback(function ($other) use ($type) {
                        if ($type === MarketingListType::TYPE_MANUAL) {
                            $mixin = MarketingListProvider::MANUAL_RESULT_ENTITIES_MIXIN;
                        } else {
                            $mixin = MarketingListProvider::RESULT_ENTITIES_MIXIN;
                        }
                        return $other['grid-mixin'] === $mixin;
                    })
                )
            )
            ->willReturn($dataGrid);

        $dataGridConfiguration = $this->dataGridProvider->getDataGridConfiguration($marketingList);

        $this->assertEquals($config, $dataGridConfiguration);
    }

    public function marketingListTypeDataProvider(): array
    {
        return [
            [MarketingListType::TYPE_MANUAL],
            [MarketingListType::TYPE_DYNAMIC]
        ];
    }
}
