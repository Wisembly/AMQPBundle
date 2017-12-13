<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessFactory
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $consolePath;

    public function __construct(string $consolePath)
    {
        $this->consolePath = $consolePath;
    }

    public function create(string $command, array $arguments, string $stdin = null, int $verbosity = OutputInterface::VERBOSITY_NORMAL): Process
    {
        switch ($verbosity) {
            case OutputInterface::VERBOSITY_DEBUG:
                $arguments[] = '-vvv';
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $arguments[] = '-vv';
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                $arguments[] = '--verbose';
                break;

            case OutputInterface::VERBOSITY_QUIET:
                $arguments[] = '--quiet';
                break;

            case OutputInterface::VERBOSITY_NORMAL:
            break;
        }

        $process = new Process(array_merge(
            [
                PHP_BINARY,
                $this->consolePath,
                $command,
            ],

            $arguments
        ));

        if ($stdin) {
            $process->setInput($stdin);
        }

        return $process;
    }
}
