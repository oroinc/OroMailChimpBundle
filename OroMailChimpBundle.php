<?php

namespace Oro\Bundle\MailChimpBundle;

use Oro\Bundle\MailChimpBundle\DependencyInjection\CompilerPass\ExtendedMergeVarsProviderPass;
use Oro\Bundle\MailChimpBundle\DependencyInjection\OroMailChimpExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMailChimpBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ExtendedMergeVarsProviderPass());
    }

    #[\Override]
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (!$this->extension) {
            $this->extension = new OroMailChimpExtension();
        }

        return $this->extension;
    }
}
