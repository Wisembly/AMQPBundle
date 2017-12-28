<?php
namespace Wisembly\AmqpBundle;

use Datetime;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Swarrot\Broker\Message;

/**
 * Publisher for AMQP Messages
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
 */
class Publisher
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var BrokerInterface */
    private $broker;

    /** @param array $gates Gates registered in our config */
    public function __construct(EventDispatcherInterface $dispatcher, BrokerInterface $broker)
    {
        $this->broker = $broker;
        $this->dispatcher = $dispatcher;
    }

    public function publish(Message $message, Gate $gate): void
    {
        $provider = $this->broker->getProducer($gate);
        $provider->publish($message, $gate->getRoutingKey());

        $this->dispatcher->dispatch(MessagePublishedEvent::NAME, new MessagePublishedEvent($message, new Datetime, $gate));
    }
}
