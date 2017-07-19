<?php
namespace Wisembly\AmqpBundle\Broker;

use InvalidArgumentException;

use AMQPQueue;
use AMQPChannel;
use AMQPExchange;
use AMQPConnection;

use AMQPException;
use AMQPChannelException;
use AMQPExchangeException;
use AMQPConnectionException;

use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider;
use Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;
use Wisembly\AmqpBundle\BrokerInterface;
use Wisembly\AmqpBundle\Exception\MessagingException;

class PeclBroker implements BrokerInterface
{
    const TYPES = [
        'direct' => AMQP_EX_TYPE_DIRECT,
        'fanout' => AMQP_EX_TYPE_FANOUT,
        'headers' => AMQP_EX_TYPE_HEADERS,
        'topic' => AMQP_EX_TYPE_TOPIC,
    ];

    /** @var AMQPChannel[] */
    private $channels = [];

    /** @var PeclPackageMessageProvider[][] */
    private $providers = [];

    /** @var PeclPackageMessagePublisher[][] */
    private $producers = [];

    /** {@inheritDoc} */
    public function getProvider(Gate $gate)
    {
        $name = $gate->getName();
        $connection = $gate->getConnection()->getName();

        if (!isset($this->providers[$connection])) {
            $this->providers[$connection] = [];
        }

        if (!isset($this->providers[$connection][$name])) {
            $channel = $this->getChannel($gate->getConnection());
            $this->declare($gate, $channel);
            $queue = new AMQPQueue($channel);
            $queue->setName($gate->getQueue());

            $this->providers[$connection][$name] = new PeclPackageMessageProvider($queue);
        }

        return $this->providers[$connection][$name];
    }

    /** {@inheritDoc} */
    public function getProducer(Gate $gate)
    {
        $name = $gate->getName();
        $connection = $gate->getConnection()->getName();

        if (!isset($this->producers[$connection])) {
            $this->producers[$connection] = [];
        }

        if (!isset($this->producers[$connection][$name])) {
            try {
                $channel = $this->getChannel($gate->getConnection());
                $this->declare($gate, $channel);
                $exchange = new AMQPExchange($channel);
                $exchange->setName($gate->getExchange());
            } catch (AMQPExchangeException $e) {
                throw new MessagingException($e);
            }

            $this->producers[$connection][$name] = new PeclPackageMessagePublisher($exchange);
        }

        return $this->producers[$connection][$name];
    }

    private function declare(Gate $gate, AMQPChannel $channel)
    {
        if (false === $gate->getAutoDeclare()) {
            return;
        }

        $options = $gate->getQueueOptions();
        $queue = new AMQPQueue($channel);
        $queue->setName($gate->getQueue());

        $flags = 0;
        if ($options['durable'] ?? false) {
            $flags |= \AMQP_DURABLE;
        }
        if ($options['passive'] ?? false) {
            $flags |= \AMQP_PASSIVE;
        }
        if ($options['exclusive'] ?? false) {
            $flags |= \AMQP_EXCLUSIVE;
        }
        if ($options['auto-delete'] ?? false) {
            $flags |= \AMQP_AUTODELETE;
        }

        $queue->setFlags($flags);
        $queue->setArguments($options['arguments'] ?? []);
        $queue->declareQueue();

        $options = $gate->getExchangeOptions();
        $exchange = new AMQPExchange($channel);
        $exchange->setName($gate->getExchange());

        $flags = 0;
        if ($options['durable'] ?? false) {
            $flags &= \AMQP_DURABLE;
        }
        if ($options['passive'] ?? false) {
            $flags &= \AMQP_PASSIVE;
        }

        $exchange->setFlags($flags);
        $exchange->setArguments($options['arguments'] ?? []);
        $exchange->setType(self::TYPES[$options['type'] ?? 'direct'] ?? 'direct');

        $queue->bind(
            $gate->getExchange(),
            $queue->getRoutingKey() ?: '',
            [] // arguments
        );
    }

    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            $channel->getConnection()->disconnect();
        }
    }

    /** {@inheritDoc} */
    public function createTemporaryQueue(Gate $gate)
    {
        $id = sha1(uniqid(mt_rand(), true));
        $key = $gate->getRoutingKey();

        // creating temporary gate
        $gate = new Gate($gate->getConnection(), $id, $gate->getExchange(), $id);
        $gate->setRoutingKey($key);

        // creating temporary queue
        $queue = new AMQPQueue($this->getChannel($gate->getConnection()));
        $queue->setName($id);
        $queue->setFlags(\AMQP_EXCLUSIVE);
        $queue->declareQueue();
        $queue->bind($gate->getExchange(), $queue->getName());

        $this->providers[$gate->getConnection()->getName()][$gate->getName()] = new PeclPackageMessageProvider($queue);

        return $gate;
    }

    /**
     * Get a channel with the connection $connection
     *
     * @param Connection $connnection Connection to use
     * @return AMQPChannel
     */
    private function getChannel(Connection $connection)
    {
        $name = $connection->getName();

        if (isset($this->channels[$name])) {
            return $this->channels[$name];
        }

        try {
            $connection = new AMQPConnection(['host' => $connection->getHost(),
                                              'port' => $connection->getPort(),
                                              'login' => $connection->getLogin(),
                                              'password' => $connection->getPassword(),
                                              'vhost' => $connection->getVhost()]);

            $connection->connect();

            return $this->channels[$name] = new AMQPChannel($connection);
        } catch (AMQPException $e) {
            throw new MessagingException($e);
        }
    }
}

