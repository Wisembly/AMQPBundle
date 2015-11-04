<?php
namespace Wisembly\AmqpBundle\Processor;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Output\OutputInterface;

use Swarrot\Broker\Message;
use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

use Swarrot\Processor\ProcessorInterface;

class CommandProcessor implements ProcessorInterface
{
    /** @var int Exit code to interpret as rabbitmq actions */
    const REQUEUE = 126;

    /** @var int Maximum number of attempts if we have to retry a command */
    const MAX_ATTEMPTS = 3;

    /** @var ProcessBuilder */
    private $builder;

    /** @var MessagePublisherInterface */
    private $publisher;

    /** @var MessageProviderInterface */
    private $provider;

    /** @var LoggerInterface */
    private $logger;

    /** @var string path to the sf console */
    private $commandPath;

    public function __construct(LoggerInterface $logger = null, ProcessBuilder $builder, MessageProviderInterface $provider, MessagePublisherInterface $publisher, $commandPath, $environment, $verbosity = OutputInterface::VERBOSITY_NORMAL)
    {
        $this->logger = $logger ?: new NullLogger;
        $this->builder = $builder;
        $this->provider = $provider;
        $this->publisher = $publisher;
        $this->verbosity = $verbosity;
        $this->commandPath = $commandPath;
        $this->environment = $environment;

        $this->builder->setTimeout(null);
    }

    public function process(Message $message, array $options)
    {
        $properties = $message->getProperties();
        $body = json_decode($message->getBody(), true);

        if (!isset($properties['wisembly_attempts'])) {
            $properties['wisembly_attempts'] = 0;
        }

        if (!isset($body['arguments'])) {
            $body['arguments'] = [];
        }

        // add environment
        $body['arguments'][] = '--env';
        $body['arguments'][] = $this->environment;

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

        ++$properties['wisembly_attempts'];

        $this->logger->info('Dispatching command', $body);

        // if no proper command given, log it and nack
        if (!isset($body['command'])) {
            $this->logger->critical('No proper command found in message', ['body' => $body]);
            $this->provider->nack($message, false);
            return;
        }

        $this->builder->setPrefix([PHP_BINARY, $this->commandPath, $body['command']]);
        $this->builder->setArguments($body['arguments']);

        $process = $this->builder->getProcess();
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

        // reset the builder
        $this->builder->setArguments([]);
        $this->builder->setPrefix([]);

        if ($process->isSuccessful()) {
            $this->logger->info('The process was successful', $body);
            $this->provider->ack($message);
            return;
        }

        $code = $process->getExitCode();
        $this->logger->error('The command failed ; aborting', ['body' => $body, 'code' => $code]);

        $this->provider->nack($message, false);

        // should we requeue it ?
        if (static::REQUEUE === $code && $properties['wisembly_attempts'] < static::MAX_ATTEMPTS) {
            $this->logger->notice('Retrying...', $body);

            $message = new Message($message->getBody(), $properties, $message->getId());
            $this->publisher->publish($message);
        }
    }
}

