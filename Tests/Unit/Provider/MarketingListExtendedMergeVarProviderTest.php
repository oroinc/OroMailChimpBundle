<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\MailChimpBundle\Provider\MarketingListExtendedMergeVarProvider;
use Oro\Bundle\MarketingListBundle\Entity\MarketingList;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MarketingListExtendedMergeVarProviderTest extends TestCase
{
    /** @var EntityFieldProvider|MockObject */
    private $entityFieldProvider;
    private $provider;

    protected function setUp(): void
    {
        $this->entityFieldProvider = $this->createMock(EntityFieldProvider::class);

        $this->provider = new MarketingListExtendedMergeVarProvider(
            $this->entityFieldProvider
        );
    }

    public function testIsApplicable()
    {
        $marketingList = new MarketingList();
        $this->assertTrue($this->provider->isApplicable($marketingList));
    }

    public function testProvideExtendedMergeVars()
    {
        $segment = new Segment();
        $segment->setDefinition(json_encode(['columns' => [['name' => 'field1', 'label' => 'field1_label']]]));
        $marketingList = new MarketingList();
        $marketingList->setSegment($segment);
        $marketingList->setEntity(User::class);
        $this->entityFieldProvider->expects($this->once())
            ->method('getEntityFields')
            ->with(
                User::class,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
                | EntityFieldProvider::OPTION_WITH_UNIDIRECTIONAL
                | EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
            )
            ->willReturn([
                'field1' => [
                    'type' => 'integer',
                    'name' => 'field1',
                    'identifier' => true
                ]
            ]);
        $mergeVars = $this->provider->provideExtendedMergeVars($marketingList);
        $this->assertEquals([['name' => 'field1', 'label' => 'field1_label', 'fieldType' => 'number']], $mergeVars);
    }
}
