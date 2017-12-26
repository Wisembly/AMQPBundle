<?php

namespace Wisembly\AmqpBundle\Command;

use Swarrot\Broker\Message;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Wisembly\AmqpBundle\GatesBag;
use Wisembly\AmqpBundle\Publisher;

/**
 * RabbitMQ Publisher
 *
 * Publish a rabbitmq messages.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class PublishCommand extends Command
{
    /** @var GatesBag */
    private $gates;

    /** @var Publisher */
    private $publisher;

    public function __construct(Publisher $publisher, GatesBag $gates)
    {
        $this->gates = $gates;
        $this->publisher = $publisher;

        parent::__construct('amqp:publish');
    }

    protected function configure()
    {
        $this
            ->setAliases(['wisembly:amqp:publish'])
            ->setDescription('Publish a message in an AMQP gate')
        ;

        $this
            ->addArgument('gate', InputArgument::REQUIRED, 'AMQP Gate to use')
            ->addArgument('message', InputArgument::REQUIRED, 'message string')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = $input->getArgument('message');
        $gate = $this->gates->get($input->getArgument('gate'));

        $this->publisher->publish(new Message($message), $gate);
        $output->writeln(sprintf('<info>Published "%s" message to "%s" queue</info>', $message, $gate->getName()));
    }
}
