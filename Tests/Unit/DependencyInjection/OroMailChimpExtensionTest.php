<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MailChimpBundle\Controller\Api\Rest\StaticSegmentController;
use Oro\Bundle\MailChimpBundle\DependencyInjection\OroMailChimpExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroMailChimpExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroMailChimpExtension());

        $expectedDefinitions = [
            StaticSegmentController::class,
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded(['oro_mailchimp']);
    }

    public function testGetAlias(): void
    {
        $extension = new OroMailChimpExtension();

        self::assertEquals(OroMailChimpExtension::ALIAS, $extension->getAlias());
    }
}
