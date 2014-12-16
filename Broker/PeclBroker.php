<?php
namespace Wisembly\AmqpBundle\Broker;

use InvalidArgumentException;

use AMQPQueue,
    AMQPChannel,
    AMQPExchange,
    AMQPConnection,

    AMQPException,
    AMQPChannelException,
    AMQPExchangeException,
    AMQPConnectionException;

use Swarrot\Broker\MessageProvider\PeclPackageMessageProvider,
    Swarrot\Broker\MessagePublisher\PeclPackageMessagePublisher;

use Wisembly\AmqpBundle\Gate,
    Wisembly\AmqpBundle\Connection,
    Wisembly\AmqpBundle\BrokerInterface,
    Wisembly\AmqpBundle\Exception\MessagingException;

class PeclBroker implements BrokerInterface
{
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
            $queue = new AMQPQueue($this->getChannel($gate->getConnection()));
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
                $exchange = new AMQPExchange($this->getChannel($gate->getConnection()));
                $exchange->setName($gate->getExchange());
            } catch (AMQPExchangeException $e) {
                throw new MessagingException($e);
            }

            $this->producers[$connection][$name] = new PeclPackageMessagePublisher($exchange);
        }

        return $this->producers[$connection][$name];
    }

    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            $channel->getConnection()->disconnect();
        }
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

