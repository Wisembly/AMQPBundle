<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use ReflectionClass;
use InvalidArgumentException;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

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

        $broker = $container->getParameter('wisembly.amqp.broker');

        if (!isset($brokers[$broker])) {
            throw new InvalidArgumentException(sprintf('Invalid broker "%s". Expected one of those : [%s]', $broker, implode(', ', array_keys($brokers))));
        }

        $container->setAlias('wisembly.amqp.broker', $brokers[$broker]);
    }
}
