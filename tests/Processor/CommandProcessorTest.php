<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use PHPUnit\Framework\TestCase;

use Prophecy\Argument;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Processor\ProcessFactory;

class CommandProcessorTest extends TestCase
{
    public function test_it_is_instantiable()
    {
        $factory = $this->prophesize(ProcessFactory::class);
        $this->assertInstanceOf(CommandProcessor::class, new CommandProcessor(null, $factory->reveal()));
    }

    public function test_options_setter()
    {
        $resolver = $this->prophesize(OptionsResolver::class);
        $resolver->setDefault('verbosity', OutputInterface::VERBOSITY_NORMAL)->shouldBeCalled();
        $resolver->setAllowedValues('verbosity', [
            OutputInterface::VERBOSITY_QUIET,
            OutputInterface::VERBOSITY_NORMAL,
            OutputInterface::VERBOSITY_VERBOSE,
            OutputInterface::VERBOSITY_VERY_VERBOSE,
            OutputInterface::VERBOSITY_DEBUG,
        ])->shouldBeCalled();

        $factory = $this->prophesize(ProcessFactory::class);

        $processor = new CommandProcessor(null, $factory->reveal());
        $processor->setDefaultOptions($resolver->reveal());
    }

    /** @expectedException Wisembly\AmqpBundle\Processor\NoCommandException */
    public function test_no_body_no_chocolate()
    {
        $message = new Message(json_encode([]));
        $factory = $this->prophesize(ProcessFactory::class);
        $factory->create(Argument::cetera())->shouldNotBeCalled();

        $processor = new CommandProcessor(null, $factory->reveal());
        $processor->process($message, ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);
    }

    public function test_successful_process()
    {
        $message = new Message(json_encode(['command' => 'foo']));

        $process = $this->prophesize(Process::class);
        $process->mustRun(Argument::type('callable'))->shouldBeCalled();

        $factory = $this->prophesize(ProcessFactory::class);
        $factory->create('foo', [], null, OutputInterface::VERBOSITY_NORMAL)->willReturn($process)->shouldBeCalled();

        $processor = new CommandProcessor(null, $factory->reveal());
        $processor->process($message, ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);
    }

    /**
     * @expectedException Wisembly\AmqpBundle\Processor\CommandFailureException
     * @expectedExceptionMessage The command "foo" failed (Error: "bar")
     */
    public function test_process_failure_triggers_exception()
    {
        $message = new Message(json_encode(['command' => 'foo']));

        $exception = $this->prophesize(ProcessFailedException::class);

        $process = $this->prophesize(Process::class);
        $process->getExitCode()->willReturn(42);
        $process->getExitCode()->willReturn(42);
        $process->getCommandLine()->willReturn('foo');
        $process->getExitCodeText()->willReturn('bar');
        $process->mustRun(Argument::type('callable'))->willThrow($exception->reveal())->shouldBeCalled();

        $factory = $this->prophesize(ProcessFactory::class);
        $factory->create('foo', [], null, OutputInterface::VERBOSITY_NORMAL)->willReturn($process)->shouldBeCalled();

        $processor = new CommandProcessor(null, $factory->reveal());
        $processor->process($message, ['verbosity' => OutputInterface::VERBOSITY_NORMAL]);
    }
}
