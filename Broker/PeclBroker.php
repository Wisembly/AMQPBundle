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

use Wisembly\AmqpBundle\BrokerInterface,
    Wisembly\AmqpBundle\Exception\MessagingException;

class PeclBroker implements BrokerInterface
{
    /** @var AMQPChannel[] */
    private $channels = [];

    /** @var string[][] */
    private $connections = [];

    /** @var PeclPackageMessageProvider[][] */
    private $providers = [];

    /** @var PeclPackageMessagePublisher[][] */
    private $producers = [];

    /** @var string[][] */
    private $gates = [];

    public function __construct(array $gates)
    {
        $this->gates = $gates;
    }

    /** {@inheritDoc} */
    public function addConnection($name, array $connection)
    {
        $this->connections[$name] = $connection;

        return $this;
    }

    /** {@inheritDoc} */
    public function getProvider($name, $connection = null)
    {
        if (!isset($this->gates[$name])) {
            throw new InvalidArgumentException(sprintf('Gate "%s" not recognized. Have you forgotten to declare it ?', $this->gates[$name]['exchange']));
        }

        if (null === $connection) {
            reset($this->connections);
            $connection = key($this->connections);
        }

        if (!isset($this->providers[$connection])) {
            $this->providers[$connection] = [];
        }

        if (!isset($this->providers[$connection][$name])) {
            $queue = new AMQPQueue($this->getChannel($connection));
            $queue->setName($this->gates[$name]['queue']);

            $this->providers[$connection][$name] = new PeclPackageMessageProvider($queue);
        }

        return $this->providers[$connection][$name];
    }

    /** {@inheritDoc} */
    public function getProducer($name, $connection = null)
    {
        if (!isset($this->gates[$name])) {
            throw new InvalidArgumentException(sprintf('Gate "%s" not recognized. Have you forgotten to declare it ?', $this->gates[$name]['exchange']));
        }

        if (null === $connection) {
            reset($this->connections);
            $connection = key($this->connections);
        }

        if (!isset($this->producers[$connection])) {
            $this->producers[$connection] = [];
        }

        if (!isset($this->producers[$connection][$name])) {
            try {
                $exchange = new AMQPExchange($this->getChannel($connection));
                $exchange->setName($this->gates[$name]['exchange']);
            } catch (AMQPExchangeException $e) {
                throw new MessagingException($e);
            }

            $this->producers[$connection][$name] = new PeclPackageMessagePublisher($exchange);
        }

        return $this->producers[$connection][$name];
    }

    /**
     * Get a channel with the connection $connection
     *
     * @param string $connnection Connection's name
     * @return AMQPChannel
     */
    private function getChannel($connection)
    {
        if (isset($this->channels[$connection])) {
            return $this->channels[$connection];
        }

        if (!isset($this->connections[$connection])) {
            throw new InvalidArgumentException(sprintf('Unknown connection "%s". Available : [%s]', $connection, implode(', ', array_keys($this->connections))));
        }

        try {
            $connexion = new AMQPConnection($this->connections[$connection]);
            $connexion->connect();

            return $this->channels[$connection] = new AMQPChannel($connexion);
        } catch (AMQPException $e) {
            throw new MessagingException($e);
        }
    }

    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            $channel->getConnection()->disconnect();
        }
    }
}
