<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use Closure;

use PHPUnit\Framework\TestCase;

use Swarrot\Consumer;
use Swarrot\Processor\Stack\StackedProcessor;

use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\UriConnection;
use Wisembly\AmqpBundle\BrokerInterface;
use Swarrot\Processor\SignalHandler\SignalHandlerProcessor;

class ConsumerFactoryTest extends TestCase
{
    public function test_it_is_instantiable()
    {
        $broker = $this->prophesize(BrokerInterface::class);
        $processor = $this->prophesize(CommandProcessor::class);

        $this->assertInstanceOf(ConsumerFactory::class, new ConsumerFactory(null, $broker->reveal(), $processor->reveal()));
    }

    public function test_it_returns_a_consumer_with_a_stacked_processor()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $provider = $this->prophesize(MessageProviderInterface::class);
        $producer = $this->prophesize(MessagePublisherInterface::class);

        $broker = $this->prophesize(BrokerInterface::class);
        $broker->getProvider($gate)->willReturn($provider)->shouldBeCalled();
        $broker->getProducer($gate)->willReturn($producer)->shouldBeCalled();

        $processor = $this->prophesize(CommandProcessor::class);

        $factory = new ConsumerFactory(null, $broker->reveal(), $processor->reveal());
        $consumer = $factory->getConsumer($gate);

        $this->assertInstanceOf(Consumer::class, $consumer);
        $this->assertInstanceOf(StackedProcessor::class, $consumer->getProcessor());
    }

    /**
     * @requires extension pcntl
     */
    public function test_the_stacked_processor_has_signal_processor_if_pcntl()
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $provider = $this->prophesize(MessageProviderInterface::class);
        $producer = $this->prophesize(MessagePublisherInterface::class);

        $broker = $this->prophesize(BrokerInterface::class);
        $broker->getProvider($gate)->willReturn($provider)->shouldBeCalled();
        $broker->getProducer($gate)->willReturn($producer)->shouldBeCalled();

        $processor = $this->prophesize(CommandProcessor::class);

        $factory = new ConsumerFactory(null, $broker->reveal(), $processor->reveal());
        $consumer = $factory->getConsumer($gate);

        $processor = $consumer->getProcessor();

        $this->assertAttributeInstanceOf(SignalHandlerProcessor::class, 'processor', $processor);
    }

    public function test_the_stacked_processor_doesnt_have_signal_processor_if_no_pcntl()
    {
        if (extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL is loaded, so no point.');
        }

        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $provider = $this->prophesize(MessageProviderInterface::class);
        $producer = $this->prophesize(MessagePublisherInterface::class);

        $broker = $this->prophesize(BrokerInterface::class);
        $broker->getProvider($gate)->willReturn($provider)->shouldBeCalled();
        $broker->getProducer($gate)->willReturn($producer)->shouldBeCalled();

        $processor = $this->prophesize(CommandProcessor::class);

        $factory = new ConsumerFactory(null, $broker->reveal(), $processor->reveal());
        $consumer = $factory->getConsumer($gate);

        $processor = $consumer->getProcessor();

        $this->assertAttributeNotInstanceOf(SignalHandlerProcessor::class, 'processor', $processor);
    }
}
