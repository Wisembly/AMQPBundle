<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Command;

use PHPUnit\Framework\TestCase;

use Prophecy\Argument;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Swarrot\Consumer;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\UriConnection;
use Wisembly\AmqpBundle\Processor\ConsumerFactory;

class ConsumerCommandTest extends TestCase
{
    public function test_it_is_instantiable()
    {
        $bag = new GatesBag;
        $factory = $this->prophesize(ConsumerFactory::class);

        $this->assertInstanceOf(ConsumerCommand::class, new ConsumerCommand($bag, $factory->reveal()));
    }

    public function test_it_calls_the_consumer_with_the_right_options()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $bag = new GatesBag;
        $bag->add($gate);

        $options = [
            'memory_limit' => null,
            'poll_interval' => 50000,
            'verbosity' => OutputInterface::VERBOSITY_NORMAL
        ];

        $input = $this->prophesize(InputInterface::class);

        $input->validate()->shouldBeCalled();
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->willReturn(false)->shouldBeCalled();
        $input->getArgument('gate')->willReturn('foo')->shouldBeCalled();
        $input->hasArgument('command')->willReturn(false)->shouldBeCalled();
        $input->getOption('memory-limit')->willReturn(null)->shouldBeCalled();
        $input->getOption('poll-interval')->willReturn(50000)->shouldBeCalled();
        $input->getOption('disable-verbosity-propagation')->willReturn(false)->shouldBeCalled();

        $output = $this->prophesize(OutputInterface::class);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);

        $consumer = $this->prophesize(Consumer::class);
        $consumer->consume($options)->shouldBeCalled();

        $factory = $this->prophesize(ConsumerFactory::class);
        $factory->getConsumer($gate)->willReturn($consumer)->shouldBeCalled();

        $command = new ConsumerCommand($bag, $factory->reveal());

        $this->assertSame(0, $command->run($input->reveal(), $output->reveal()));
    }

    public function test_disabling_verbosity_propagation_makes_process_quiet()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $bag = new GatesBag;
        $bag->add($gate);

        $options = [
            'memory_limit' => null,
            'poll_interval' => 50000,
            'verbosity' => OutputInterface::VERBOSITY_QUIET
        ];

        $input = $this->prophesize(InputInterface::class);

        $input->validate()->shouldBeCalled();
        $input->bind(Argument::any())->shouldBeCalled();
        $input->isInteractive()->willReturn(false)->shouldBeCalled();
        $input->getArgument('gate')->willReturn('foo')->shouldBeCalled();
        $input->hasArgument('command')->willReturn(false)->shouldBeCalled();
        $input->getOption('memory-limit')->willReturn(null)->shouldBeCalled();
        $input->getOption('poll-interval')->willReturn(50000)->shouldBeCalled();
        $input->getOption('disable-verbosity-propagation')->willReturn(true)->shouldBeCalled();

        $output = $this->prophesize(OutputInterface::class);
        $output->getVerbosity()->willReturn(OutputInterface::VERBOSITY_NORMAL);

        $consumer = $this->prophesize(Consumer::class);
        $consumer->consume($options)->shouldBeCalled();

        $factory = $this->prophesize(ConsumerFactory::class);
        $factory->getConsumer($gate)->willReturn($consumer)->shouldBeCalled();

        $command = new ConsumerCommand($bag, $factory->reveal());

        $this->assertSame(0, $command->run($input->reveal(), $output->reveal()));
    }
}
