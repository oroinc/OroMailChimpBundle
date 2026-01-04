<?php

namespace Oro\Bundle\MailChimpBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Extended merge vars provider compiler pass class.
 */
class ExtendedMergeVarsProviderPass implements CompilerPassInterface
{
    public const COMPOSITE_PROVIDER_ID = 'oro_mailchimp.extended_merge_var.composite_provider';
    public const PROVIDER_TAG_NAME     = 'oro_mailchimp.extended_merge_vars.provider';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $compositeProvider = $container->getDefinition(self::COMPOSITE_PROVIDER_ID);
        $providers = $container->findTaggedServiceIds(self::PROVIDER_TAG_NAME);

        foreach ($providers as $serviceId => $tags) {
            $ref = new Reference($serviceId);
            $compositeProvider->addMethodCall('addProvider', [$ref]);
        }
    }
}
