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

        $gates = [];
        $definition = $container->getDefinition('wisembly.amqp.gates');

        foreach ($container->getParameter('wisembly.amqp.gates') as $name => $config) {
            $gateDefinition = new Definition('Wisembly\\AmqpBundle\\Gate', [$name, $config['exchange'], $config['queue']]);

            $gateDefinition->addMethodCall('setConnection', [$config['connection']])
                           ->addMethodCall('setRoutingKey', [$config['routing_key']])
                           ->addMethodCall('setExtras', [$config['extras']]);

            $gates[$name] = $gateDefinition;
        }

        //$container->getParameterBag()->remove('wisembly.amqp.gates');
        $definition->addArgument($gates);
    }
}

