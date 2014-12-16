<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use ReflectionClass,
    InvalidArgumentException;

use Symfony\Component\DependencyInjection\Definition,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Tranforms the gate into a GateBag full of Gate objects
 *
 * @author Baptiste Clavie <baptiste@wisembly.com>
 */
class GatePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('wisembly.amqp.gates')) {
            return;
        }

        if (!$container->hasDefinition('wisembly.amqp.gates')) {
            return;
        }

        $connections = [];

        foreach ($container->getParameter('wisembly.amqp.connections') as $name => $config) {
            $connections[$name] = new Definition('Wisembly\\AmqpBundle\\Connection', [$name, $config['host'], $config['port'], $config['login'], $config['password'], $config['vhost']]);
        }

        $gates = [];
        $definition = $container->getDefinition('wisembly.amqp.gates');

        foreach ($container->getParameter('wisembly.amqp.gates') as $name => $config) {
            $gateDefinition = new Definition('Wisembly\\AmqpBundle\\Gate', [$name, $config['exchange'], $config['queue']]);

            $gateDefinition->addMethodCall('setConnection', [$connections[$config['connection']]])
                           ->addMethodCall('setRoutingKey', [$config['routing_key']])
                           ->addMethodCall('setExtras', [$config['extras']]);

            $gates[$name] = $gateDefinition;
        }

        $definition->addArgument($gates);

        $container->getParameterBag()->remove('wisembly.amqp.gates');
        $container->getParameterBag()->remove('wisembly.amqp.connections');
    }
}

