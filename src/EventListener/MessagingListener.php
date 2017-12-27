<?php
namespace Wisembly\AmqpBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Wisembly\AmqpBundle\DataCollector\AMQPDataCollector;

class MessagingListener implements EventSubscriberInterface
{
    private $collector;

    public function __construct(AMQPDataCollector $collector)
    {
        $this->collector = $collector;
    }

    public static function getSubscribedEvents()
    {
        return [MessagePublishedEvent::NAME => 'onMessageSent'];
    }

    public function onMessageSent(MessagePublishedEvent $event)
    {
        $gate = $event->getGate();
        $routingKey = null === $gate->getRoutingKey() ? '' : sprintf(' : %s', $gate->getRoutingKey());

        $this->collector->addMessage([
            'gate' => $gate->getName(),
            'message' => $event->getMessage(),
            'connection' => $gate->getConnection(),
            'published_at' => $event->getPublishedAt(),
            'exchange' => "{$gate->getExchange()}{$routingKey}",
        ]);

    }
}

