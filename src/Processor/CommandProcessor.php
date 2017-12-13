<?php
namespace Wisembly\AmqpBundle\Processor;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\Ack\AckProcessor;

class CommandProcessor implements ProcessorInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $verbosity;

    /** @var ProcessFactory */
    private $factory;

    public function __construct(LoggerInterface $logger = null, ProcessFactory $factory, int $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->factory = $factory;
        $this->verbosity = $verbosity;
        $this->logger = $logger ?: new NullLogger;
    }

    public function process(Message $message, array $options)
    {
        $body = json_decode($message->getBody(), true);

        $this->logger->info('Dispatching command', $body);

        // if no proper command given, log it
        if (!isset($body['command'])) {
            $this->logger->critical('No proper command found in message', ['body' => $body]);
            throw new NoCommandException;
        }

        $process = $this->factory->create(
            $body['command'],
            $body['arguments'] ?? [],
            $body['stdin'] ?? null,
            $this->verbosity
        );

        try {
            $process->mustRun(function ($type, $data) {
                switch ($type) {
                    case Process::OUT:
                        $this->logger->info($data);
                        break;

                    case Process::ERR:
                        $this->logger->error($data);
                        break;
                }
            });

            $this->logger->info('The process was successful', $body);
        } catch (ProcessFailedException $e) {
            $this->logger->error('The command failed ; aborting', ['body' => $body, 'code' => $process->getExitCodeText()]);

            throw new CommandFailureException($body, $process, $e);
        }
    }
}
