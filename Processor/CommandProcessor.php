<?php
namespace Wisembly\AmqpBundle\Processor;

use Psr\Log\LoggerInterface;

use Symfony\Component\Process\ProcessBuilder;

use Swarrot\Broker\Message,
    Swarrot\Broker\MessageProvider\MessageProviderInterface,
    Swarrot\Broker\MessagePublisher\MessagePublisherInterface,

    Swarrot\Processor\ProcessorInterface;

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

    public function __construct(LoggerInterface $logger, ProcessBuilder $builder, MessageProviderInterface $provider, MessagePublisherInterface $publisher, $commandPath, $environment, $verbosity = false)
    {
        $this->logger      = $logger;
        $this->builder     = $builder;
        $this->provider    = $provider;
        $this->publisher   = $publisher;
        $this->commandPath = $commandPath;
        $this->verbosity   = $verbosity;
        $this->environment = $environment;

        $this->builder->setTimeout(null);
    }

    public function process(Message $message, array $options)
    {
        $properties = $message->getProperties();
        $body       = json_decode($message->getBody(), true);

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
        if ($this->verbosity) {
            $body['arguments'][] = '--verbose';
        }

        ++$properties['wisembly_attempts'];

        $this->logger->info('Dispatching command', $body);

        $this->builder->setPrefix([$this->commandPath, $body['command']]);
        $this->builder->setArguments($body['arguments']);

        $process = $this->builder->getProcess();
        $process->run();

        // reset the builder
        $this->builder->setArguments([]);
        $this->builder->setPrefix([]);

        if ($process->isSuccessful()) {
            $this->logger->info('The process was successful', $body);
            $this->provider->ack($message);
            return;
        }

        $code = $process->getExitCode();
        $this->logger->warning('The command failed ; aborting', ['body' => $body, 'code' => $code, 'error' => $process->getErrorOutput()]);

        $this->provider->nack($message, false);

        // should we requeue it ?
        if (static::REQUEUE === $code && $properties['wisembly_attempts'] < static::MAX_ATTEMPTS) {
            $this->logger->notice('Retrying...', $body);

            $message = new Message($message->getBody(), $properties, $message->getId());
            $this->publisher->publish($message);
        }
    }
}

