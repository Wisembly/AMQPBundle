<?php
namespace Wisembly\AmqpBundle;

use Datetime;
use UnexpectedValueException;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Swarrot\Broker\Message;

use Wisembly\AMQPBundle\Bag;
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
    public function __construct(EventDispatcherInterface $dispatcher, BrokerInterface $broker, Bag $gates)
    {
        $this->gates      = $gates;
        $this->broker     = $broker;
        $this->dispatcher = $dispatcher;
    }

    public function publish(Message $message, $gate)
    {
        if (!$this->gates->has($gate)) {
            throw new UnexpectedValueException(sprintf('Unknown gate "%s" selected. Available gates are : [%s]', $gate, implode(array_keys($this->gates->all()), ', ')));
        }

        $gate = $this->gates->get($gate);

        $provider = $this->broker->getProducer($gate);
        $provider->publish($message, $gate->getRoutingKey());

        $this->dispatcher->dispatch(MessagePublishedEvent::NAME, new MessagePublishedEvent($message, new Datetime, $gate));
    }
}

