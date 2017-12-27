<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;

use Prophecy\Prophecy\ProphecyInterface;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\DependencyInjection\WisemblyAmqpExtension;

class BrokerPassTest extends TestCase
{
    public function test_it_can_be_initialized()
    {
        $this->assertInstanceOf(BrokerPass::class, new BrokerPass);
    }

    public function test_valid_broker_is_used_as_alias_for_interface()
    {
        $container = $this->getContainerProphecy('foo');
        $container->setAlias(BrokerInterface::class, 'foo')->shouldBeCalled();

        $pass = new BrokerPass;
        $pass->process($container->reveal());
    }

    public function test_valid_broker_alias_is_used_as_alias_for_interface()
    {
        $container = $this->getContainerProphecy('baz');
        $container->setAlias(BrokerInterface::class, 'bar')->shouldBeCalled();
        $container->setAlias(BrokerInterface::class, 'baz')->shouldNotBeCalled();

        $pass = new BrokerPass;
        $pass->process($container->reveal());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid broker "qux". Expected one of those : [foo, bar, baz]
     */
    public function test_invalid_broker_triggers_exception()
    {
        $container = $this->getContainerProphecy('qux');
        $container->setAlias(BrokerInterface::class, 'qux')->shouldNotBeCalled();

        $pass = new BrokerPass;
        $pass->process($container->reveal());
    }

    private function getContainerProphecy(string $broker): ProphecyInterface
    {
        $services = [
            'foo' => [],
            'bar' => [
                ['alias' => 'baz'],
            ],
        ];

        $extension = $this->prophesize(WisemblyAmqpExtension::class);
        $extension->getConfig([])->willReturn(['broker' => $broker])->shouldBeCalled();

        $container = $this->prophesize(ContainerBuilder::class);
        $container->findTaggedServiceIds('wisembly.amqp.broker')->willReturn($services)->shouldBeCalled();
        $container->getExtensionConfig('wisembly_amqp')->willReturn([])->shouldBeCalled();
        $container->getExtension('wisembly_amqp')->willReturn($extension)->shouldBeCalled();

        return $container;
    }
}
