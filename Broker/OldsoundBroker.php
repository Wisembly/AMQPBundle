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

use Wisembly\AmqpBundle\BrokerInterface,
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

    /** @var string[][] */
    private $gates = [];

    public function __construct(array $gates)
    {
        $this->gates = $gates;
    }

    /** {@inheritDoc} */
    public function addConnection($name, array $connection)
    {
        $connection = new AMQPLazyConnection($connection['host'],
                                             $connection['port'],
                                             $connection['login'],
                                             $connection['password'],
                                             $connection['vhost']);

        $connection->set_close_on_destruct(true);

        $this->connections[$name] = $connection;

        return $this;
    }

    /** {@inheritDoc} */
    public function getProvider($name, $connection = null)
    {
        if (!isset($this->gates[$name])) {
            throw new InvalidArgumentException(sprintf('Gate "%s" not recognized. Have you forgotten to declare it ?', $name));
        }

        if (null === $connection) {
            reset($this->connections);
            $connection = key($this->connections);
        }

        if (!isset($this->providers[$connection])) {
            $this->providers[$connection] = [];
        }

        if (!isset($this->providers[$connection][$name])) {
            $this->providers[$connection][$name] = new PhpAmqpLibMessageProvider($this->getChannel($connection), $this->gates[$name]['queue']);
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
            $this->producers[$connection][$name] = new PhpAmqpLibMessagePublisher($this->getChannel($connection), $this->gates[$name]['exchange']);
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
            return $this->channels[$connection] = $this->connections[$connection]->channel();
        } catch (AMQPProtocolException $e) {
            throw new MessagingException($e);
        }
    }
}
