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

use Swarrot\Processor\RPC\RpcServerProcessor;
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

    /** @var ProcessFactory */
    private $factory;

    public function __construct(?LoggerInterface $logger, BrokerInterface $broker, GatesBag $gates, ProcessFactory $factory)
    {
        $this->gates = $gates;
        $this->broker = $broker;
        $this->factory = $factory;
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

        $processor = new CommandProcessor(
            $this->logger,
            $this->factory,
            true === $input->getOption('disable-verbosity-propagation') ? OutputInterface::VERBOSITY_QUIET : $output->getVerbosity()
        );

        // if we want a rpc mechanism, let's wrap a rpc server processor
        if (true === $input->getOption('rpc')) {
            $processor = new RpcServerProcessor($processor, $producer, $this->logger);
        }

        // Wrap processor in an Swarrot ExceptionCatcherProcessor to avoid breaking processor if an error occurs
        $processor = new ExceptionCatcherProcessor($processor, $this->logger);
        $options = [];

        $processor = new SignalHandlerProcessor($processor, $this->logger);

        // we apply a memory limit to the consumer
        if (null !== $input->getOption('memory-limit')) {
            $processor = new MemoryLimitProcessor($processor, $this->logger);
            $options['memory_limit'] = (int) $input->getOption('memory-limit');
        }

        $consumer = new Consumer($provider, $processor);
        $consumer->consume(['poll_interval' => $input->getOption('poll-interval')]);
    }
}
