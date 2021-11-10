<?php

namespace Oro\Bundle\MailChimpBundle;

use Oro\Bundle\MailChimpBundle\DependencyInjection\CompilerPass\ExtendedMergeVarsProviderPass;
use Oro\Bundle\MailChimpBundle\DependencyInjection\OroMailChimpExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The OroMailChimpBundle class.
 */
class OroMailChimpBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ExtendedMergeVarsProviderPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroMailChimpExtension();
        }

        return $this->extension;
    }
}
