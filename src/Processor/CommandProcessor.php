<?php
namespace Wisembly\AmqpBundle\Processor;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Output\OutputInterface;

use Swarrot\Broker\Message;
use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

use Swarrot\Processor\ProcessorInterface;
use Swarrot\Processor\Ack\AckProcessor;

class CommandProcessor implements ProcessorInterface
{
    /** @var MessageProviderInterface */
    private $provider;

    /** @var LoggerInterface */
    private $logger;

    /** @var string path to the sf console */
    private $commandPath;

    /** @var int */
    private $verbosity;

    /** @var string */
    private $environment;

    public function __construct(LoggerInterface $logger = null, MessageProviderInterface $provider, string $commandPath, string $environment, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->logger = $logger ?: new NullLogger;
        $this->provider = $provider;
        $this->verbosity = $verbosity;
        $this->commandPath = $commandPath;
        $this->environment = $environment;
    }

    public function process(Message $message, array $options)
    {
        $body = json_decode($message->getBody(), true);

        if (!isset($body['arguments'])) {
            $body['arguments'] = [];
        }

        // add environment
        if (null !== $this->environment) {
            $body['arguments'][] = '--env';
            $body['arguments'][] = $this->environment;
        }

        // add verbosity
        switch ($this->verbosity) {
            case OutputInterface::VERBOSITY_DEBUG:
                $body['arguments'][] = '-vvv';
                break;

            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $body['arguments'][] = '-vv';
                break;

            case OutputInterface::VERBOSITY_VERBOSE:
                $body['arguments'][] = '--verbose';
                break;

            case OutputInterface::VERBOSITY_QUIET:
                $body['arguments'][] = '--quiet';
                break;

            case OutputInterface::VERBOSITY_NORMAL:
            break;
        }

        $this->logger->info('Dispatching command', $body);

        // if no proper command given, log it and nack
        if (!isset($body['command'])) {
            $this->logger->critical('No proper command found in message', ['body' => $body]);
            $this->provider->nack($message, false);
            return;
        }

        $process = new Process(array_merge(
            [
                PHP_BINARY,
                $this->commandPath,
                $body['command'],
            ],

            $body['arguments']
        ));

        // a stdin is provided, let's send it to the command
        if (isset($body['stdin'])) {
            $process->setInput($body['stdin']);

            // remove the stdin for the logs
            unset($body['stdin']);
        }

        $process->run(function ($type, $data) {
            switch ($type) {
                case Process::OUT:
                    $this->logger->info($data);
                    break;

                case Process::ERR:
                    $this->logger->error($data);
                    break;
            }
        });

        // todo Do not ack here, let it be acked by the AckProcessor
        if ($process->isSuccessful()) {
            @trigger_error(
                \E_USER_DEPRECATED,
                sprintf(
                    'From 2.0, the message won\'t be acked on successful process. Use the %s processor instead.',
                    AckProcessor::class
                )
            );

            $this->logger->info('The process was successful', $body);
            $this->provider->ack($message);
            return;
        }

        $code = $process->getExitCode();
        $this->logger->error('The command failed ; aborting', ['body' => $body, 'code' => $code]);

        @trigger_error(
            \E_USER_DEPRECATED,
            sprintf(
                'From 2.0, if a process fail, it will trigger an exception and _not_ nack the message. Use the %s processor instead.',
                AckProcessor::class
            )
        );

        $this->provider->nack($message, false);
    }
}

