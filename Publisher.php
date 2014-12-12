<?php
namespace Wisembly\AmqpBundle;

use Datetime,
    UnexpectedValueException;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\EventListener\MessagePublishedEvent;

/**
 * Publisher for AMQP Messages
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class Publisher
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var BrokerInterface */
    private $broker;

    /** @var array */
    private $gates;

    /** @param array $gates Gates registered in our config */
    public function __construct(EventDispatcherInterface $dispatcher, BrokerInterface $broker, array $gates)
    {
        $this->gates      = $gates;
        $this->broker     = $broker;
        $this->dispatcher = $dispatcher;
    }

    public function publish(Message $message, $gate)
    {
        if (!isset($this->gates[$gate])) {
            throw new UnexpectedValueException(sprintf('Unknown gate "%s" selected. Available gates are : [%s]', $gate, implode(array_keys($this->gates), ', ')));
        }

        $provider = $this->broker->getProducer($gate, $this->gates[$gate]['connection']);
        $provider->publish($message, $this->gates[$gate]['routing_key']);

        $this->dispatcher->dispatch(MessagePublishedEvent::NAME, new MessagePublishedEvent($message, new Datetime, $gate, $this->gates[$gate]));
    }
}

