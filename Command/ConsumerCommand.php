<?php

namespace Wisembly\AmqpBundle\Command;

use Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,

    Symfony\Component\Process\ProcessBuilder,
    Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Swarrot\Consumer,

    Swarrot\Processor\RPC\RpcServerProcessor,
    Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor;

use Wisembly\AmqpBundle\Processor\CommandProcessor;

/**
 * RabbitMQ Consumer
 *
 * Consumer for rabbitmq messages. Dispatch it to the right command.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class ConsumerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('wisembly:amqp:consume')
             ->setDescription('Consume all messages launched through an AMQP gate');

        $this->addArgument('gate', InputArgument::REQUIRED, 'AMQP Gate to use');

        $this->addOption('rpc', null, InputOption::VALUE_NONE, 'Use a RPC mechanism ?')
             ->addOption('poll-interval', null, InputOption::VALUE_REQUIRED, 'poll interval, in micro-seconds', 50000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $gate = $input->getArgument('gate');

        $broker = $container->get('wisembly.amqp.broker');
        $logger = $container->get('monolog.logger.consumer');
        $gate = $container->get('wisembly.amqp.gates')->get($gate);
        $environment = $container->get('kernel')->getEnvironment();

        $provider = $broker->getProvider($gate);
        $producer = $broker->getProducer($gate);

        $processor = new CommandProcessor($logger, new ProcessBuilder, $provider, $producer, $container->getParameter('wisembly.core.path.console'), $container->getParameter('wisembly.core.path.php'), $environment, $output->getVerbosity());

        // if we want a rpc mechanism, let's wrap a rpc server processor
        if (true === $input->getOption('rpc')) {
            $processor = new RpcServerProcessor($processor, $producer, $logger);
        }

        // Wrap processor in an Swarrot ExceptionCatcherProcessor to avoid breaking processor if an error occurs
        $consumer  = new Consumer($provider, new ExceptionCatcherProcessor($processor, $logger));

        $consumer->consume(['poll_interval' => $input->getOption('poll-interval')]);
    }
}
