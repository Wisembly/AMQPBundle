<?php

namespace Wisembly\AmqpBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('wisembly_amqp');

        $this->addAmqpNode($rootNode);

        return $treeBuilder;
    }

    private function addAmqpNode(NodeDefinition $root)
    {
        $root
            ->children()
                ->scalarNode('default_connection')
                    ->info('Default connection to use')
                    ->defaultNull()
                ->end()

                ->scalarNode('broker')
                    ->info('Broker to use')
                    ->isRequired()
                ->end()

                ->arrayNode('connections')
                    ->info('Connections to AMQP to use')
                    ->useAttributeAsKey('name')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->isRequired()->end()
                            ->integerNode('port')->isRequired()->end()
                            ->scalarNode('login')->isRequired()->end()
                            ->scalarNode('password')->isRequired()->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('gates')
                    ->info('Access gate for each dialog with AMQP')
                    ->useAttributeAsKey('name')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->booleanNode('auto_declare')->info('Does the queue and the exchange be declared before use them')->defaultTrue()->end()
                            ->scalarNode('connection')->info('Connection to use with this gate')->defaultNull()->end()
                            ->scalarNode('exchange')->info('Exchange point associated to this gate')->isRequired()->end()
                            ->scalarNode('routing_key')->info('Routing key to use when sending messages through this gate')->defaultNull()->end()
                            ->scalarNode('queue')->info('Queue to fetch the information from')->isRequired()->end()
                            ->append($this->queueOptions())
                            ->append($this->exchangeOptions())
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function queueOptions()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('queue_options');

        $node->info('Broker options to declare the queue')
            ->children()
                ->booleanNode('passive')->defaultFalse()->end()
                ->booleanNode('durable')->defaultTrue()->end()
                ->booleanNode('exclusive')->defaultFalse()->end()
                ->booleanNode('auto_delete')->defaultFalse()->end()
                ->arrayNode('arguments')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $node;
    }

    private function exchangeOptions()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('exchange_options');

        $node->info('Broker options to declare the exchange')
            ->children()
                ->scalarNode('type')->defaultFalse()->end()
                ->booleanNode('passive')->defaultFalse()->end()
                ->booleanNode('durable')->defaultTrue()->end()
                ->booleanNode('auto_delete')->defaultFalse()->end()
                ->booleanNode('internal')->defaultFalse()->end()
                ->arrayNode('arguments')
                    ->useAttributeAsKey('name')
                    ->prototype('scalar')->end()
                ->end()
            ->end();

        return $node;
    }
}
