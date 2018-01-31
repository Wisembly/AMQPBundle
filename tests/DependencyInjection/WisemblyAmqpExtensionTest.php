<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\DependencyInjection;

use PHPUnit\Framework\TestCase;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\Connection;
use Wisembly\AmqpBundle\UriConnection;

use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\Broker\PeclBroker;

use Wisembly\AmqpBundle\Processor\ConsumerFactory;
use Wisembly\AmqpBundle\Processor\CommandProcessor;

class WisemblyAmqpExtensionTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $extension = new WisemblyAmqpExtension;
        $this->assertInstanceOf(WisemblyAmqpExtension::class, $extension);
    }

    public function test_it_loads()
    {
        $container = $this->getContainer();

        $this->assertTrue(true);
    }

    public function test_it_does_not_register_pecl_broker_if_not_available()
    {
        if (extension_loaded('pecl')) {
            $this->markTestSkipped('The pecl extension is loaded');
        }

        $container = $this->getContainer([]);

        $this->assertFalse($container->hasDefinition(PeclBroker::class));
    }

    public function test_it_registers_a_broker_parameter_to_be_used_in_compiler_pass()
    {
        $container = $this->getContainer([]);

        $this->assertTrue($container->hasParameter('wisembly.amqp.config.broker'));
    }

    /**
     * @requires extension amqp
     */
    public function test_it_registers_pecl_broker_if_available()
    {
        $container = $this->getContainer([]);

        $this->assertTrue($container->hasDefinition(PeclBroker::class));
    }

    public function test_it_loads_the_right_connection_class()
    {
        $container = $this->getContainer([
            'connections' => [
                'foo' => [
                    'uri' => 'amqp://localhost',
                ],

                'bar' => [
                    'host' => 'bar',
                ],
            ],

            'gates' => [
                'foo' => [
                    'connection' => 'foo',
                    'exchange' => 'bar',
                    'queue' => 'bar',
                ],
                'bar' => [
                    'connection' => 'bar',
                    'exchange' => 'bar',
                    'queue' => 'bar'
                ],
            ]
        ]);

        $bagDefinition = $container->getDefinition(GatesBag::class);

        // a little hackish... and kinda unstable. To watch out for.
        list(list(,list($fooDefinition)), list(,list($barDefinition))) = $bagDefinition->getMethodCalls();

        list($connectionDefinition,) = $fooDefinition->getArguments();
        $this->assertSame(UriConnection::class, $connectionDefinition->getClass());

        list($connectionDefinition,) = $barDefinition->getArguments();
        $this->assertSame(Connection::class, $connectionDefinition->getClass());

    }

    public function test_it_picks_the_default_connection_if_none_specified()
    {
        $container = $this->getContainer([
            'default_connection' => 'foo',

            'gates' => [
                'bar' => [
                    'exchange' => 'bar',
                    'queue' => 'bar'
                ],
            ]
        ]);

        $bagDefinition = $container->getDefinition(GatesBag::class);
        $calls = $bagDefinition->getMethodCalls();

        /** @var Definition $gateDefinition */
        foreach ($calls as list(,list($gateDefinition))) {
            $arguments = $gateDefinition->getArguments();

            $connectionDefinition = array_shift($arguments);
            $arguments = $connectionDefinition->getArguments();

            $this->assertSame(UriConnection::class, $connectionDefinition->getClass());
            $this->assertSame('foo', $arguments[0]);
            $this->assertSame('amqp://localhost', $arguments[1]);
        }
    }

    public function test_it_changes_the_monolog_tag()
    {
        $container = $this->getContainer([
            'logger_channel' => 'foo'
        ]);

        foreach ([CommandProcessor::class, ConsumerFactory::class] as $service) {
            $def = $container->getDefinition($service);
            $tags = $def->getTag('monolog.logger');

            foreach ($tags as $attributes) {
                $this->assertSame('foo', $attributes['channel']);
            }
        }
    }

    private function getContainer(array $config = []): ContainerBuilder
    {
        $broker = $this->prophesize(BrokerInterface::class)->reveal();

        $container = new ContainerBuilder;

        $configs = [
            [
                'default_connection' => 'foo',
                'broker' => get_class($broker),
                'console_path' => 'foo',
                'connections' => [
                    'foo' => 'amqp://localhost',
                ],
                'gates' => [
                    'foo' => [
                        'exchange' => 'bar',
                        'queue' => 'baz'
                    ]
                ]
            ],

            $config
        ];

        $extension = new WisemblyAmqpExtension;
        $extension->load($configs, $container);

        return $container;
    }
}
