<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use PHPUnit\Framework\TestCase;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessFactoryTest extends TestCase
{
    public function test_it_is_instantiable()
    {
        $this->assertInstanceOf(ProcessFactory::class, new ProcessFactory('foo'));
    }

    public function test_it_prepares_a_process()
    {
        $process = new Process(
            [
                PHP_BINARY,
                'foo',
                'bar',
            ]
        );

        $commandLine = $process->getCommandLine();

        $factory = new ProcessFactory('foo');
        $process = $factory->create('bar', []);

        $this->assertSame($commandLine, $process->getCommandLine());
    }

    /** @dataProvider verbosityProvider */
    public function test_the_verbosity_is_added(int $level, string $argument)
    {
        $process = new Process(
            array_filter([
                PHP_BINARY,
                'foo',
                'bar',
                $argument
            ])
        );

        $commandLine = $process->getCommandLine();

        $factory = new ProcessFactory('foo');
        $process = $factory->create('bar', [], null, $level);

        $this->assertSame($commandLine, $process->getCommandLine());
    }

    public function verbosityProvider(): iterable
    {
        return [
            'debug' => [OutputInterface::VERBOSITY_DEBUG, '-vvv'],
            'very verbose' => [OutputInterface::VERBOSITY_VERY_VERBOSE, '-vv'],
            'verbose' => [OutputInterface::VERBOSITY_VERBOSE, '--verbose'],
            'quiet' => [OutputInterface::VERBOSITY_QUIET, '--quiet'],
            'normal' => [OutputInterface::VERBOSITY_NORMAL, ''],
        ];
    }

    public function test_it_passes_the_stdin_if_given()
    {
        $factory = new ProcessFactory('foo');
        $process = $factory->create('bar', [], 'baz');

        $this->assertSame('baz', $process->getInput());
    }
}
