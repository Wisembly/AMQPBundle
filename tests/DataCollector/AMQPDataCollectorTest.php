<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\DataCollector;

use DatetimeImmutable;
use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\UriConnection;

class AMQPDataCollectorTest extends TestCase
{
    public function test_it_can_be_initialized()
    {
        $this->assertInstanceOf(AMQPDataCollector::class, new AMQPDataCollector);
    }

    public function test_collect_without_routing_key()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $collector = new AMQPDataCollector;
        $collector->addMessage($gate, $message = new Message, $date = new DatetimeImmutable);

        list($collected) = $collector->getMessages();

        $this->assertSame($gate, $collected->getGate());
        $this->assertSame($message, $collected->getMessage());
        $this->assertSame($date, $collected->getPublishedAt());
        $this->assertSame($connection, $collected->getConnection());
        $this->assertSame('bar', $collected->getExchange());
    }

    public function test_collect_with_routing_key()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz', 'qux');

        $collector = new AMQPDataCollector;
        $collector->addMessage($gate, $message = new Message, $date = new DatetimeImmutable);

        list($collected) = $collector->getMessages();

        $this->assertSame($gate, $collected->getGate());
        $this->assertSame($message, $collected->getMessage());
        $this->assertSame($date, $collected->getPublishedAt());
        $this->assertSame($connection, $collected->getConnection());
        $this->assertSame('bar : qux', $collected->getExchange());
    }

    /**
     * @group legacy
     * @expectedDeprecation Passing only one array parameter is deprecated since 2.1 and will be removed on 3.0. Please pass at least 3 arguments.
     */
    public function test_legacy_on_array_parameter()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $collector = new AMQPDataCollector;
        $collector->addMessage([
            'gate' => $gate,
            'message' => $message = new Message,
            'published_at' => $date = new DatetimeImmutable
        ]);

        list($collected) = $collector->getMessages();

        $this->assertSame($gate, $collected->getGate());
        $this->assertSame($message, $collected->getMessage());
        $this->assertSame($date, $collected->getPublishedAt());
        $this->assertSame($connection, $collected->getConnection());
        $this->assertSame('bar', $collected->getExchange());
    }

    /**
     * @group legacy
     * @dataProvider legacy_failures_provider
     */
    public function test_legacy_triggers_exceptions($message, ...$parameters)
    {
        $this->expectExceptionMessage($message);
        $this->expectException(InvalidArgumentException::class);

        $collector = new AMQPDataCollector;
        $collector->addMessage(...$parameters);
    }

    public function legacy_failures_provider(): iterable
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        return [
            // new stuff, 3 args (At least) : Wisembly\AmqpBundle\Gate, Swarrot\Broker\Message, DatetimeInterface
            'not enough parameters' => ['Expected at least 3 arguments, got 2', 'foo', 'bar'],
            'first parameter not a Gate' => ['The first parameter should be a Wisembly\AmqpBundle\Gate, string given.', 'foo', 'bar', 'baz'],
            'second parameter not a Message' => ['The second parameter should be a Swarrot\Broker\Message, string given.', $gate, 'bar', 'baz'],
            'third parameter not a DatetimeInterface' => ['The third parameter should be a DatetimeInterface, string given.', $gate, new Message, 'baz'],

            // legacy, array sole parameter
            'legacy, not an array' => ['Expected an array as sole parameter, got string', 'foo'],
        ];
    }
}
