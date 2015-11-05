<?php

namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;

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
            $connections[$name] = new Definition(Connection::class, [$name, $config['host'], $config['port'], $config['login'], $config['password'], $config['vhost']]);
        }

        $gates = [];
        $definition = $container->getDefinition('wisembly.amqp.gates');

        foreach ($container->getParameter('wisembly.amqp.gates') as $name => $config) {
            $id = sprintf('wisembly.amqp.gates.%s', $name);
            $gate = $container->register($id, Gate::class);

            $gate
                ->addArgument($connections[$config['connection']])
                ->addArgument($name)
                ->addArgument($config['exchange'])
                ->addArgument($config['queue'])
                ->addArgument($config['routing_key']);

            $definition->addMethodCall('add', [new Reference($id)]);
        }
    }
}

