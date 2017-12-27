<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\EventListener;

use Datetime;

use PHPUnit\Framework\TestCase;

use Wisembly\AmqpBundle\DataCollector\AMQPDataCollector;
use Swarrot\Broker\Message;
use Wisembly\AmqpBundle\UriConnection;
use Wisembly\AmqpBundle\Gate;

class MessagingListenerTest extends TestCase
{
    public function test_the_listener_can_be_instantiated(): void
    {
        $collector = $this->prophesize(AMQPDataCollector::class);
        $listener = new MessagingListener($collector->reveal());

        $this->assertInstanceOf(MessagingListener::class, $listener);
    }

    public function test_it_adds_a_message_in_the_collector_on_published_event_without_routing_key()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');

        $event = new MessagePublishedEvent(
            $message = new Message,
            $datetime = new Datetime,
            $gate = new Gate($connection, 'foo', 'bar', 'baz')
        );

        $collector = $this->prophesize(AMQPDataCollector::class);
        $collector->addMessage([
            'gate' => 'foo',
            'message' => $message,
            'connection' => $connection,
            'published_at' => $datetime,
            'exchange' => 'bar',
        ])->shouldBeCalled();

        $listener = new MessagingListener($collector->reveal());
        $listener->onMessageSent($event);
    }

    public function test_it_adds_a_message_in_the_collector_on_published_event_with_routing_key()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');

        $event = new MessagePublishedEvent(
            $message = new Message,
            $datetime = new Datetime,
            $gate = new Gate($connection, 'foo', 'bar', 'baz', 'qux')
        );

        $collector = $this->prophesize(AMQPDataCollector::class);
        $collector->addMessage([
            'gate' => 'foo',
            'message' => $message,
            'connection' => $connection,
            'published_at' => $datetime,
            'exchange' => 'bar : qux',
        ])->shouldBeCalled();

        $listener = new MessagingListener($collector->reveal());
        $listener->onMessageSent($event);
    }
}