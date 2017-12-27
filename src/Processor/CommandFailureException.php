<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use Throwable;
use RuntimeException;

use Wisembly\AmqpBundle\AmqpExceptionInterface;
use Symfony\Component\Process\Process;

final class CommandFailureException extends RuntimeException implements AmqpExceptionInterface
{
    /** @var string[] */
    private $body;

    /** @var Process */
    private $process;

    public function __construct(array $body, Process $process, Throwable $previous = null)
    {
        $this->body = $body;
        $this->process = $process;

        parent::__construct(
            "The command \"{$process->getCommandLine()}\" failed {Error: \"{$process->getExitCodeText()}\")",
            $process->getExitCode(),
            $previous
        );
    }

    public function getBody(): array
    {
        return $this->body;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
