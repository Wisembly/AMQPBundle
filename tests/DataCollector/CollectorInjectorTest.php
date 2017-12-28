<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\DataCollector;

use Datetime;
use DatetimeInterface;

use PHPUnit\Framework\TestCase;

use Prophecy\Argument;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\UriConnection;
use Wisembly\AmqpBundle\MessagePublishedEvent;

class CollectorInjectorTest extends TestCase
{
    public function test_the_listener_can_be_instantiated(): void
    {
        $collector = $this->prophesize(AMQPDataCollector::class);
        $listener = new CollectorInjector($collector->reveal());

        $this->assertInstanceOf(CollectorInjector::class, $listener);
    }

    public function test_it_adds_a_message_in_the_collector_on_published_event()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');

        $event = new MessagePublishedEvent(
            $message = new Message,
            $datetime = new Datetime,
            $gate = new Gate($connection, 'foo', 'bar', 'baz')
        );

        $collector = $this->prophesize(AMQPDataCollector::class);
        $collector->addMessage(
            Argument::type(Gate::class),
            Argument::type(Message::class),
            Argument::type(DatetimeInterface::class)
        )->shouldBeCalled();

        $listener = new CollectorInjector($collector->reveal());
        $listener->onMessageSent($event);
    }
}
