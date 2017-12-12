<?php
namespace Wisembly\AmqpBundle\Broker;

use InvalidArgumentException;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPLazyConnection;

use PhpAmqpLib\Exception\AMQPProtocolException;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Exception\AMQPProtocolConnectionException;

use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessageProvider\PhpAmqpLibMessageProvider;

use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;
use Swarrot\Broker\MessagePublisher\PhpAmqpLibMessagePublisher;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;
use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\MessagingException;

class PhpAmqpLibBroker implements BrokerInterface
{
    /** @var AMQPChannel[] */
    private $channels = [];

    /** @var AMQPLazyConnection[] */
    private $connections = [];

    /** @var PhpAmqpLibMessageProvider[][] */
    private $providers = [];

    /** @var PhpAmqpLibMessagePublisher[][] */
    private $producers = [];

    public function getProvider(Gate $gate): MessageProviderInterface
    {
        $name = $gate->getName();
        $connection = $gate->getConnection()->getName();

        if (!isset($this->providers[$connection])) {
            $this->providers[$connection] = [];
        }

        if (!isset($this->providers[$connection][$name])) {
            $channel = $this->getChannel($gate->getConnection());
            $this->declare($gate, $channel);
            $this->providers[$connection][$name] = new PhpAmqpLibMessageProvider(
                $channel,
                $gate->getQueue()
            );
        }

        return $this->providers[$connection][$name];
    }

    public function getProducer(Gate $gate): MessagePublisherInterface
    {
        $name = $gate->getName();
        $connection = $gate->getConnection()->getName();

        if (!isset($this->producers[$connection])) {
            $this->producers[$connection] = [];
        }

        if (!isset($this->producers[$connection][$name])) {
            $channel = $this->getChannel($gate->getConnection());
            $this->declare($gate, $channel);
            $this->producers[$connection][$name] = new PhpAmqpLibMessagePublisher(
                $channel,
                $gate->getExchange()
            );
        }

        return $this->producers[$connection][$name];
    }

    private function declare(Gate $gate, AMQPChannel $channel): void
    {
        if (false === $gate->getAutoDeclare()) {
            return;
        }

        $options = $gate->getQueueOptions();
        $channel->queue_declare(
            $gate->getQueue(),
            $options['passive'] ?? false,
            $options['durable'] ?? true,
            $options['exclusive'] ?? false,
            $options['auto-delete'] ?? false,
            false, // nowait
            $options['arguments'] ?? null,
            null // ticket
        );

        $options = $gate->getExchangeOptions();
        $channel->exchange_declare(
            $gate->getExchange(),
            $options['type'] ?? 'direct',
            $options['passive'] ?? false,
            $options['durable'] ?? true,
            $options['auto-delete'] ?? false,
            false, // internal
            false, // nowait
            $options['arguments'] ?? null,
            null // ticket
        );

        $channel->queue_bind(
            $gate->getQueue(),
            $gate->getExchange(),
            $gate->getRoutingKey(),
            false, // nowait
            null, // arguments
            null // ticket
        );
    }

    public function createTemporaryQueue(Gate $gate): Gate
    {
        $key = $gate->getRoutingKey();
        $name = sha1(uniqid(mt_rand(), true));
        $connection = $gate->getConnection()->getName();

        // creating temporary gate
        $gate = new Gate($gate->getConnection(), $name, $gate->getExchange(), $name);
        $gate->setRoutingKey($key);

        // creating temporary queuei
        if (!isset($this->providers[$connection])) {
            $this->providers[$connection] = [];
        }

        $channel = $this->getChannel($gate->getConnection());
        $channel->queue_declare($name, false, false, true, false);
        $channel->queue_bind($gate->getQueue(), $gate->getExchange(), $gate->getQueue());

        $this->providers[$connection][$name] = new PhpAmqpLibMessageProvider($this->getChannel($gate->getConnection()), $gate->getQueue());

        return $gate;
    }

    private function getChannel(Connection $connection): AMQPChannel
    {
        $name = $connection->getName();

        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        $connection = $this->getConnection($connection);

        try {
            return $this->channels[$name] = $connection->channel();
        } catch (AMQPProtocolException $e) {
            throw new MessagingException($e);
        }
    }

    private function getConnection(Connection $connection): AMQPLazyConnection
    {
        $name = $connection->getName();

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $connection = new AMQPLazyConnection($connection->getHost(),
                                             $connection->getPort(),
                                             $connection->getLogin(),
                                             $connection->getPassword(),
                                             $connection->getVhost());

        $connection->set_close_on_destruct(true);

        return $this->connections[$name] = $connection;
    }
}
