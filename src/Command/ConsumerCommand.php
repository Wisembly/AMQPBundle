<?php
namespace Wisembly\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Exception\InvalidArgumentException;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

use Psr\Log\NullLogger;
use Psr\Log\LoggerInterface;

use Swarrot\Consumer;

use Swarrot\Processor\Ack\AckProcessor;
use Swarrot\Processor\RPC\RpcServerProcessor;
use Swarrot\Processor\Stack\StackedProcessor;
use Swarrot\Processor\MemoryLimit\MemoryLimitProcessor;
use Swarrot\Processor\SignalHandler\SignalHandlerProcessor;
use Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor;

use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\BrokerInterface;

use Wisembly\AmqpBundle\Processor\ProcessFactory;
use Wisembly\AmqpBundle\Processor\CommandProcessor;

/**
 * RabbitMQ Consumer
 *
 * Consumer for rabbitmq messages. Dispatch it to the right command.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class ConsumerCommand extends Command
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var GatesBag */
    private $gates;

    /** @var BrokerInterface */
    private $broker;

    /** @var CommandProcessor */
    private $processor;

    public function __construct(?LoggerInterface $logger, BrokerInterface $broker, GatesBag $gates, CommandProcessor $processor)
    {
        $this->gates = $gates;
        $this->broker = $broker;
        $this->processor = $processor;
        $this->logger = $logger ?: new NullLogger;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('wisembly:amqp:consume')
             ->setDescription('Consume all messages launched through an AMQP gate');

        $this->addArgument('gate', InputArgument::REQUIRED, 'AMQP Gate to use');

        $this->addOption('rpc', null, InputOption::VALUE_NONE, 'Use a RPC mechanism ?')
             ->addOption('disable-verbosity-propagation', null, InputOption::VALUE_NONE, 'Do not spread the verbosity to the child command')
             ->addOption('poll-interval', null, InputOption::VALUE_REQUIRED, 'poll interval, in micro-seconds', 50000)
             ->addOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'memory limit use by the consumer, in MB');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gate = $input->getArgument('gate');
        $gate = $this->gates->get($gate);

        $provider = $this->broker->getProvider($gate);
        $processor = $this->processor;
        $middlewares = [$processor];

        if (true === $input->getOption('rpc')) {
            $processor = new RpcServerProcessor($processor, $producer, $this->logger);
            $middlewares = [$processor];
        }

        $processor = new AckProcessor($processor, $provider, $this->logger);
        $middlewares = [$processor];

        $processor = new SignalHandlerProcessor($processor, $this->logger);
        $middlewares = [$processor];

        $options = [
            'poll_interval' => $input->getOption('poll-interval'),
            'verbosity' => true === $input->getOption('disable-verbosity-propagation') ? OutputInterface::VERBOSITY_QUIET : $output->getVerbosity(),
        ];

        if (null !== $input->getOption('memory-limit')) {
            $processor = new MemoryLimitProcessor($processor, $this->logger);
            $middlewares = [$processor];

            $options['memory_limit'] = (int) $input->getOption('memory-limit');
        }

        $processor = new ExceptionCatcherProcessor($processor, $this->logger);
        $middlewares = [$processor];

        $processor = new StackedProcessor($processor, $middlewares);

        $consumer = new Consumer($provider, $processor);
        $consumer->consume($options);
    }
}
