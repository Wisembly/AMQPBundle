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
        $config = $event->getConfig();
        $config['routing_key'] = null === $config['routing_key'] ? '' : sprintf(' : %s', $config['routing_key']);

        $this->collector->addMessage(['gate'         => $event->getGate(),
                                      'extras'       => $config['extras'],
                                      'message'      => $event->getMessage(),
                                      'connection'   => $config['connection'],
                                      'published_at' => $event->getPublishedAt(),
                                      'exchange'     => $config['exchange'] . $config['routing_key']]);

    }
}

