<?php
namespace Wisembly\AmqpBundle\Broker;

use InvalidArgumentException;

use PhpAmqpLib\Channel\AMQPChannel,
    PhpAmqpLib\Connection\AMQPLazyConnection,

    PhpAmqpLib\Exception\AMQPProtocolException,
    PhpAmqpLib\Exception\AMQPProtocolChannelException,
    PhpAmqpLib\Exception\AMQPProtocolConnectionException;

use Swarrot\Broker\MessageProvider\PhpAmqpLibMessageProvider,
    Swarrot\Broker\MessagePublisher\PhpAmqpLibMessagePublisher;

use Wisembly\AmqpBundle\Gate,
    Wisembly\AmqpBundle\Connection,
    Wisembly\AmqpBundle\BrokerInterface,
    Wisembly\AmqpBundle\Exception\MessagingException;

class OldsoundBroker implements BrokerInterface
{
    /** @var AMQPChannel[] */
    private $channels = [];

    /** @var AMQPLazyConnection[] */
    private $connections = [];

    /** @var PhpAmqpLibMessageProvider[][] */
    private $providers = [];

    /** @var PhpAmqpLibMessagePublisher[][] */
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
            $this->providers[$connection][$name] = new PhpAmqpLibMessageProvider($this->getChannel($gate->getConnection()), $gate->getQueue());
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
            $this->producers[$connection][$name] = new PhpAmqpLibMessagePublisher($this->getChannel($gate->getConnection()), $gate->getExchange());
        }

        return $this->producers[$connection][$name];
    }

    /**
     * Get a channel with the connection $connection
     *
     * @return AMQPChannel
     */
    private function getChannel(Connection $connection)
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

    /** @return AMQPLazyConnection */
    private function getConnection(Connection $connection)
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

