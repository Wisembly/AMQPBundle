<?php
namespace Wisembly\AmqpBundle;

/**
 * Value object for an AMQP gate
 *
 * @author Baptiste Clavié <baptiste@eisembly.com>
 */
class Gate
{
    /** @var string Gate's name */
    private $name;

    /** @var string Exchange to use */
    private $exchange;

    /** @var string Queue to use */
    private $queue;

    /** @var Connection Connection to use */
    private $connection;

    /** @var string Routing key */
    private $key = null;

    /**
     * @param string $name Gate's name
     * @param string $exchange Exchange's name
     * @param string $queue Queue's name
     * @param string|null $key routing key to use
     */
    public function __construct(Connection $connection, $name, $exchange, $queue, $key = null)
    {
        $this->key = $key;
        $this->name = $name;
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->connection = $connection;
    }

    /** @return string Gate's name */
    public function getName()
    {
        return $this->name;
    }

    /** @return string Exchange to use */
    public function getExchange()
    {
        return $this->exchange;
    }

    /** @return string Queue to use */
    public function getQueue()
    {
        return $this->queue;
    }

    /** @return string Routing key */
    public function getRoutingKey()
    {
        return $this->key;
    }

    /** @return string Connection's name to use */
    public function getConnection()
    {
        return $this->connection;
    }
}

