<?php

declare(strict_types=1);

namespace ApplicationManagerTools\AmDriver\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('am_driver');
        /** @var ArrayNodeDefinition $root */
        $root = $treeBuilder->getRootNode();

        $root
            ->children()
                ->scalarNode('am_base_url')->defaultValue('%env(AM_DRIVER_AM_BASE_URL)%')->end()
                ->scalarNode('source')->defaultValue('%env(AM_DRIVER_SOURCE)%')->end()
                ->scalarNode('data_dir')->defaultValue('%kernel.project_dir%/var/am-driver')->end()
                ->scalarNode('consumption_webhook_token')->defaultValue('%env(AM_DRIVER_CONSUMPTION_WEBHOOK_TOKEN)%')->end()
                ->scalarNode('orchestration_callback_token')->defaultValue('%env(AM_DRIVER_ORCHESTRATION_CALLBACK_TOKEN)%')->end()
                ->scalarNode('orchestration_command_token')->defaultValue('%env(AM_DRIVER_ORCHESTRATION_COMMAND_TOKEN)%')->end()
                ->scalarNode('operational_state_token')->defaultValue('%env(AM_DRIVER_OPERATIONAL_STATE_TOKEN)%')->end()
                ->floatNode('http_timeout')->defaultValue(10.0)->end()
                ->integerNode('consumption_max_retries')->defaultValue(3)->end()
                ->integerNode('consumption_retry_delay_ms')->defaultValue(500)->end()
                ->scalarNode('route_prefix')
                    ->defaultValue(ReceiverRoutePaths::DEFAULT_ROUTE_PREFIX)
                    ->info('URL prefix for receptacle routes (e.g. "am" or "internal/am").')
                ->end()
                ->scalarNode('orchestration_commands_path')
                    ->defaultNull()
                    ->info('Override full path; default is derived from route_prefix.')
                ->end()
                ->scalarNode('operational_state_path')
                    ->defaultNull()
                    ->info('Override full path; default is derived from route_prefix.')
                ->end()
                ->scalarNode('expected_tenant_id')->defaultNull()->end()
                ->scalarNode('expected_instance_id')->defaultNull()->end()
            ->end();

        return $treeBuilder;
    }
}
