<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use ReflectionClass;
use InvalidArgumentException;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Wisembly\AmqpBundle\BrokerInterface;

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

        if (false === $container->hasParameter('wisembly.amqp.config.broker')) {
            throw new InvalidArgumentException('The expected container parameter "wisembly.amqp.config.broker" is missing');
        }

        $broker = $container->getParameter('wisembly.amqp.config.broker');

        if (!isset($brokers[$broker])) {
            throw new InvalidArgumentException(sprintf('Invalid broker "%s". Expected one of those : [%s]', $broker, implode(', ', array_keys($brokers))));
        }

        $container->setAlias(BrokerInterface::class, $brokers[$broker]);
    }
}
