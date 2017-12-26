<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use ReflectionClass;
use InvalidArgumentException;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Symfony\Component\Config\Definition\Processor;

use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\DependencyInjection\Configuration;

/**
 * Determine and instanciate which RabbitMq Broker to use
 *
 * @author Baptiste Clavie <baptiste@wisembly.com>
 */
class BrokerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $brokers = [];

        foreach ($container->findTaggedServiceIds('wisembly.amqp.broker') as $id => $tags) {
            $brokers[$id] = $id;

            foreach ($tags as $tag) {
                if (isset($tag['alias'])) {
                    $brokers[$tag['alias']] = &$brokers[$id];
                }
            }
        }

        // a bit copied from the extension, but no choice here to retrieve the config
        // without going through parameters...
        $extension = $container->getExtension('wisembly_amqp');
        $configs = $container->getExtensionConfig('wisembly_amqp');

        $processor = new Processor;
        $config = $processor->processConfiguration(new Configuration, $configs);

        if (!isset($brokers[$config['broker']])) {
            throw new InvalidArgumentException(sprintf('Invalid broker "%s". Expected one of those : [%s]', $config['broker'], implode(', ', array_keys($brokers))));
        }

        $container->setAlias(BrokerInterface::class, $brokers[$config['broker']]);
    }
}
