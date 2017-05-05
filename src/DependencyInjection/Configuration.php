<?php
namespace Wisembly\AmqpBundle\DependencyInjection;

use InvalidArgumentException;

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

                ->scalarNode('console_path')
                    ->info('Path to sf console binary')
                    ->isRequired()
                ->end()

                ->arrayNode('connections')
                    ->info('Connections to AMQP to use')
                    ->useAttributeAsKey('name')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function ($v) {
                                if (false === $parse = parse_url($v)) {
                                    throw new InvalidArgumentException('Could not parse uri');
                                }

                                if (!isset($parse['scheme'])) {
                                    throw new InvalidArgumentException('Missing scheme.');
                                }

                                if (!in_array(strtolower($parse['scheme']), ['amqp', 'amqps'])) {
                                    throw new InvalidArgumentException(sprintf('Invalid scheme. Expected "amqp(s)", had "%s"', $parse['scheme']));
                                }

                                if (!isset($parse['host'])) {
                                    throw new InvalidArgumentException('Missing host');
                                }

                                return [
                                    'host' => $parse['host'],
                                    'port' => $parse['port'] ?? null,
                                    'login' => $parse['user'] ?? null,
                                    'password' => $parse['pass'] ?? null,
                                    'vhost' => $parse['path'] ?? null,
                                    'query' => $parse['query'] ?? null
                                ];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('host')->isRequired()->end()

                            ->integerNode('port')
                                ->defaultNull
                                ->beforeNormalization()
                                    ->ifEmpty()
                                    ->thenUnset()
                                ->end()
                            ->end()

                            ->scalarNode('login')
                                ->defaultNull()
                                ->beforeNormalization()
                                    ->ifEmpty()
                                    ->thenUnset()
                                ->end()
                            ->end()

                            ->scalarNode('password')
                                ->defaultNull()
                                ->beforeNormalization()
                                    ->ifEmpty()
                                    ->thenUnset()
                                ->end()
                            ->end()

                            ->scalarNode('vhost')
                                ->defaultNull()
                                ->beforeNormalization()
                                    ->ifEmpty()
                                    ->thenUnset()
                                ->end()
                            ->end()

                            ->scalarNode('query')
                                ->defaultNull()
                                ->beforeNormalization()
                                    ->ifEmpty()
                                    ->thenUnset()
                                ->end()
                            ->end()
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
                            ->scalarNode('routing_key')->info('Routing key to use when sending messages through this gate')->defaultNull()->end()

                            ->append($this->addQueueNode())
                            ->append($this->addExchangeNode())
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addQueueNode()
    {
        $builder = new TreeBuilder;
        $node = $builder->root('queue');

        return $node
            ->info('Queue to fetch the information from')
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifString()
                ->then(function ($v) { return ['name' => $v]; })
            ->end()
            ->children()
                ->scalarNode('name')->isRequired()->end()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('passive')->defaultFalse()->end()
                        ->booleanNode('durable')->defaultTrue()->end()
                        ->booleanNode('exclusive')->defaultFalse()->end()
                        ->booleanNode('auto_delete')->defaultFalse()->end()
                        ->arrayNode('arguments')
                            ->validate()
                                ->ifEmpty()
                                ->thenUnset()
                            ->end()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addExchangeNode()
    {
        $builder = new TreeBuilder;
        $node = $builder->root('exchange');

        return $node
            ->info('Exchange point associated to this gate')
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->beforeNormalization()
                ->ifString()
                ->then(function ($v) { return ['name' => $v]; })
            ->end()
            ->children()
                ->scalarNode('name')->isRequired()->end()
                ->arrayNode('options')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('type')->defaultNull()->end()
                        ->booleanNode('passive')->defaultFalse()->end()
                        ->booleanNode('durable')->defaultTrue()->end()
                        ->booleanNode('auto_delete')->defaultFalse()->end()
                        ->booleanNode('internal')->defaultFalse()->end()
                        ->arrayNode('arguments')
                            ->validate()
                                ->ifEmpty()
                                ->thenUnset()
                            ->end()
                            ->useAttributeAsKey('name')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
