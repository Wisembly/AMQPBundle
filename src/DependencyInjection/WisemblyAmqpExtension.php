<?php

namespace Wisembly\AmqpBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class WisemblyAmqpExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $this->registerGates($container, $loader, $config);
        $this->registerCommands($container, $loader, $config);

        $loader->load('brokers.xml');
        $container->getParameterBag()->set('wisembly.amqp.broker', $config['broker']);

        $loader->load('profiler.xml');
    }

    private function registerGates(ContainerBuilder $container, Loader\FileLoader $loader, array $configuration)
    {
        $loader->load('amqp.xml');

        // no default connection ? Take the first one
        if (null === $configuration['default_connection'] || !isset($configuration['connections'][$configuration['default_connection']])) {
            reset($configuration['connections']);
            $configuration['default_connection'] = key($configuration['connections']);
        }

        // put the default connection on top
        $default = $configuration['connections'][$configuration['default_connection']];
        unset($configuration['connections'][$configuration['default_connection']]);

        // tip : http://php.net/manual/fr/function.array-unshift.php#106570
        $tmp = array_reverse($configuration['connections'], true);
        $tmp[$configuration['default_connection']] = $default;
        $configuration['connections'] = array_reverse($tmp, true);

        $connections = [];

        foreach ($configuration['connections'] as $name => $connection) {
            $connections[$name] = new Definition(Connection::class);

            $connections[$name]
                ->addArgument($name)
                ->addArgument($connection['host'])
                ->addArgument($connection['port'] ?? null)
                ->addArgument($connection['login'] ?? null)
                ->addArgument($connection['password'] ?? null)
                ->addArgument($connection['vhost'] ?? null)
                ->addArgument($connection['query'] ?? null)
            ;
        }

        $bagDefinition = $container->getDefinition('wisembly.amqp.gates');

        foreach ($configuration['gates'] as $name => $gate) {
            if (null === $gate['connection']) {
                $gate['connection'] = $configuration['default_connection'];
            }

            $gateDefinition = new Definition(Gate::class);

            $gateDefinition
                ->addArgument($connections[$gate['connection']])
                ->addArgument($name)
                ->addArgument($gate['exchange']['name'])
                ->addArgument($gate['queue']['name'])
                ->addArgument($gate['routing_key'])
                ->addArgument($gate['auto_declare'])
                ->addArgument($gate['queue']['options'])
                ->addArgument($gate['exchange']['options']);

            $bagDefinition->addMethodCall('add', [$gateDefinition]);
        }
    }

    private function registerCommands(ContainerBuilder $container, Loader\FileLoader $loader, array $configuration)
    {
        $loader->load('commands.xml');

        $parameterBag = $container->getParameterBag();

        $definition = $container->getDefinition('wisembly.amqp.command.consumer');
        $definition->replaceArgument(3, $configuration['console_path']);
    }
}
