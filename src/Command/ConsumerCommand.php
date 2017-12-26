<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\Processor\ConsumerFactory;

/**
 * RabbitMQ Consumer
 *
 * Consumer for rabbitmq messages. Dispatch it to the right command.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class ConsumerCommand extends Command
{
    /** @var GatesBag */
    private $gates;

    /** @var ConsumerFactory */
    private $factory;

    public function __construct(GatesBag $gates, ConsumerFactory $factory)
    {
        $this->gates = $gates;
        $this->factory = $factory;

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

        $options = [
            'memory_limit' => $input->getOption('memory-limit'),
            'poll_interval' => $input->getOption('poll-interval'),
            'verbosity' => true === $input->getOption('disable-verbosity-propagation')
                ? OutputInterface::VERBOSITY_QUIET
                : $output->getVerbosity(),
        ];

        $consumer = $this->factory->getConsumer($gate);
        $consumer->consume($options);
    }
}
