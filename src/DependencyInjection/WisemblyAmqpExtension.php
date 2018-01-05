<?php
namespace Wisembly\AmqpBundle\DependencyInjection;

use InvalidArgumentException;

use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\GatesBag;

use Wisembly\AmqpBundle\Connection;
use Wisembly\AmqpBundle\UriConnection;

use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\Broker\PeclBroker;

use Wisembly\AmqpBundle\Processor\ProcessFactory;
use Wisembly\AmqpBundle\Processor\ConsumerFactory;
use Wisembly\AmqpBundle\Processor\CommandProcessor;

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
        $config = $this->getConfig($configs);
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('amqp.xml');
        $this->registerGates($container, $config);

        $loader->load('processors.xml');

        $container
            ->getDefinition(ProcessFactory::class)
            ->replaceArgument('$consolePath', $config['console_path'])
        ;

        $loader->load('commands.xml');
        $loader->load('brokers.xml');

        // remove pecl broker if not available
        if (!extension_loaded('amqp')) {
            $container->removeDefinition(PeclBroker::class);
        }

        $loader->load('profiler.xml');

        $container->registerForAutoconfiguration(BrokerInterface::class)
            ->addTag('wisembly.amqp.broker')
        ;

        // dynamically change the monolog.logger tag
        foreach ([ConsumerFactory::class, CommandProcessor::class] as $service) {
            $def = $container->getDefinition($service);
            $tags = $def->getTag('monolog.logger');
            $def->clearTag('monolog.logger');

            foreach ($tags as $attributes) {
                $attributes['channel'] = $config['logger_channel'];
                $def->addTag('monolog.logger', $attributes);
            }
        }
    }

    public function getConfig(array $configs): array
    {
        return $this->processConfiguration(new Configuration, $configs);
    }

    private function registerGates(ContainerBuilder $container, array $configuration)
    {
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
            if (isset($connection['uri'])) {
                $connections[$name] = new Definition(UriConnection::class);

                $connections[$name]
                    ->addArgument($name)
                    ->addArgument($connection['uri'])
                ;

                continue;
            }

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

        $bagDefinition = $container->getDefinition(GatesBag::class);

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
}
