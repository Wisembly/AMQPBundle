<?php

namespace Wisembly\AmqpBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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

        $this->loadAmqpConfiguration($container, $loader, $config['amqp']);
    }

    private function loadAmqpConfiguration(ContainerBuilder $container, Loader\FileLoader $loader, array $configuration)
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

        foreach ($configuration['gates'] as &$gate) {
            if (null === $gate['connection']) {
                $gate['connection'] = $configuration['default_connection'];
            }
        }

        foreach ($configuration as $key => $value) {
            $container->setParameter('wisembly.amqp.' . $key, $value);
        }

        $loader->load('rabbitmq.xml');
    }
}
