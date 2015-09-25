<?php

namespace Wisembly\AmqpBundle\Command;

use Swarrot\Broker\Message;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;


/**
 * RabbitMQ Consumer
 *
 * Consumer for rabbitmq messages. Dispatch it to the right command.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class PublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('wisembly:amqp:publish')
             ->setDescription('Publish a message in an AMQP gate');

        $this->addArgument('gate', InputArgument::REQUIRED, 'AMQP Gate to use');
        $this->addArgument('message', InputArgument::REQUIRED, 'message string');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gate = $input->getArgument('gate');
        $message = $input->getArgument('message');

        $body = ['message' => $message];
        $this->getContainer()->get('wisembly.amqp.publisher')->publish(new Message(json_encode($body)), $gate);
        $output->writeln(sprintf('<info>Published "%s" message to "%s" queue</info>', $message, $gate));
    }
}
