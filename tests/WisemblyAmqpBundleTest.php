<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use Prophecy\Argument;
use PHPUnit\Framework\TestCase;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Wisembly\AmqpBundle\DependencyInjection\Compiler\BrokerPass;

class WisemblyAmqpBundleTest extends TestCase
{
    public function test_the_bundle_can_be_instantiated()
    {
        $bundle = new WisemblyAmqpBundle;
        $this->assertInstanceOf(WisemblyAmqpBundle::class, $bundle);
    }

    public function test_the_broker_pass_should_be_registered()
    {
        $container = $this->prophesize(ContainerBuilder::class);
        $container->addCompilerPass(Argument::type(BrokerPass::class))->shouldBeCalled();

        $bundle = new WisemblyAmqpBundle;
        $bundle->build($container->reveal());
    }
}
