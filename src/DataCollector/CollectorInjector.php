<?php
namespace Wisembly\AmqpBundle\DataCollector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Wisembly\AmqpBundle\DataCollector\AMQPDataCollector;
use Wisembly\AmqpBundle\MessagePublishedEvent;

class CollectorInjector implements EventSubscriberInterface
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
        $this->collector->addMessage(
            $event->getGate(),
            $event->getMessage(),
            $event->getPublishedAt()
        );
    }
}

