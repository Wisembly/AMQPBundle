<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Command;

use PHPUnit\Framework\TestCase;

use Prophecy\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\Publisher;
use Wisembly\AmqpBundle\UriConnection;

class PublishCommandTest extends TestCase
{
    public function test_it_is_instantiable()
    {
        $bag = new GatesBag;
        $publisher = $this->prophesize(Publisher::class);

        $this->assertInstanceOf(PublishCommand::class, new PublishCommand($publisher->reveal(), $bag));
    }

    public function test_it_calls_the_consumer_with_the_right_options()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $bag = new GatesBag;
        $bag->add($gate);

        $input = $this->prophesize(InputInterface::class);

        $input->validate()->shouldBeCalled();
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->willReturn(false)->shouldBeCalled();
        $input->getArgument('gate')->willReturn('foo')->shouldBeCalled();
        $input->getArgument('message')->willReturn('bar')->shouldBeCalled();
        $input->hasArgument('command')->willReturn(false)->shouldBeCalled();

        $output = $this->prophesize(OutputInterface::class);
        $output->writeln('<info>Published "bar" message to "foo" queue</info>')->shouldBeCalled();

        $publisher = $this->prophesize(Publisher::class);
        $publisher->publish(Argument::type(Message::class), $gate)->shouldBeCalled();

        $command = new PublishCommand($publisher->reveal(), $bag);

        $this->assertSame(0, $command->run($input->reveal(), $output->reveal()));
    }
}
