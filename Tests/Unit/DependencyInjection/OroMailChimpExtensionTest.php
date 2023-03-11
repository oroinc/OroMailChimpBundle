<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MailChimpBundle\DependencyInjection\OroMailChimpExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroMailChimpExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroMailChimpExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
        self::assertSame(
            [
                [
                    'settings' => [
                        'resolved' => true,
                        'static_segment_sync_mode' => ['value' => 'on_update', 'scope' => 'app']
                    ]
                ]
            ],
            $container->getExtensionConfig('oro_mailchimp')
        );
    }

    public function testGetAlias(): void
    {
        self::assertEquals('oro_mailchimp', (new OroMailChimpExtension())->getAlias());
    }
}
