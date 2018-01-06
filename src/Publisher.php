<?php
namespace Wisembly\AmqpBundle;

use Datetime;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Swarrot\Broker\Message as SwarrotMessage;

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

    /** @var GatesBag */
    private $bag;

    /** @param array $gates Gates registered in our config */
    public function __construct(EventDispatcherInterface $dispatcher, BrokerInterface $broker, GatesBag $bag)
    {
        $this->bag = $bag;
        $this->broker = $broker;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Gate|string $gate Gate to use. If string, will try to fetch it from the gates bag.
     */
    public function publish(SwarrotMessage $message, $gate): void
    {
        if (!$gate instanceof Gate) {
            $gate = $this->bag->get($gate);
        }

        $provider = $this->broker->getProducer($gate);
        $provider->publish($message, $gate->getRoutingKey());

        $this->dispatcher->dispatch(MessagePublishedEvent::NAME, new MessagePublishedEvent($message, new Datetime, $gate));
    }
}
