<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use PHPUnit\Framework\TestCase;

use Prophecy\Argument;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Swarrot\Broker\Message;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

class PublisherTest extends TestCase
{
    public function test_the_publisher_can_be_instantiated(): void
    {
        $broker = $this->prophesize(BrokerInterface::class);
        $dispatcher = $this->prophesize(EventDispatcherInterface::class);

        $publisher = new Publisher($dispatcher->reveal(), $broker->reveal());

        $this->assertInstanceOf(Publisher::class, $publisher);
    }

    public function test_publisher_publishes_messages_and_dispatch_event(): void
    {
        $message = new Message;

        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $callback = function (MessagePublishedEvent $event) use ($message, $gate): bool {
            return
                $gate === $event->getGate()
                && $message === $event->getMessage()
            ;
        };

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher->dispatch(MessagePublishedEvent::NAME, Argument::that($callback))->shouldBeCalled();

        $provider = $this->prophesize(MessagePublisherInterface::class);
        $provider->publish($message, null)->shouldBeCalled();

        $broker = $this->prophesize(BrokerInterface::class);
        $broker->getProducer($gate)->willReturn($provider)->shouldBeCalled();

        $publisher = new Publisher($dispatcher->reveal(), $broker->reveal());
        $publisher->publish($message, $gate);
    }
}
