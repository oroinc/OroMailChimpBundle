<?php

namespace Oro\Bundle\MailChimpBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public const ROOT_NODE = 'oro_mailchimp';
    public const STATIC_SEGMENT_SYNC_MODE_ON_UPDATE = 'on_update';
    public const STATIC_SEGMENT_SYNC_MODE_SCHEDULED = 'scheduled';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'static_segment_sync_mode' => ['value' => self::STATIC_SEGMENT_SYNC_MODE_ON_UPDATE],
            ]
        );

        return $treeBuilder;
    }
}
