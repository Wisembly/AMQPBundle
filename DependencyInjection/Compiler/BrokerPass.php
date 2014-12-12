<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use ReflectionClass,
    InvalidArgumentException;

use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Determine and instanciate which RabbitMq Broker to use
 *
 * @author Baptiste Clavie <baptiste@wisembly.com>
 */
class BrokerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->has('wisembly.amqp.broker')) {
            return;
        }

        $brokers = [];

        foreach ($container->findTaggedServiceIds('wisembly.amqp.broker') as $id => $tag) {
            $brokers[isset($tag[0]['alias']) ? $tag[0]['alias'] : $id] = $id;
        }

        $gates       = $container->getParameter('wisembly.amqp.gates');
        $broker      = $container->getParameter('wisembly.amqp.broker');
        $connections = $container->getParameter('wisembly.amqp.connections');

        if (!isset($brokers[$broker])) {
            throw new InvalidArgumentException(sprintf('Invalid broker "%s". Expected one of those : [%s]', $broker, implode(', ', array_keys($brokers))));
        }

        $broker     = $brokers[$broker];
        $definition = $container->getDefinition($broker);

        $reflection = new ReflectionClass($definition->getClass());

        if (!$reflection->implementsInterface('Wisembly\\AmqpBundle\\BrokerInterface')) {
            throw new InvalidArgumentException(sprintf('The provided broker "%s" is not valid.', $broker));
        }

        foreach ($connections as $name => $connection) {
            $definition->addMethodCall('addConnection', [$name, $connection]);
        }

        // add the gates parameter
        $definition->addArgument($gates);

        $container->setAlias('wisembly.amqp.broker', $broker);
    }
}
