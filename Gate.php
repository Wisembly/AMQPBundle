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

    /** @var string Connection's name to use */
    private $connection = 'default';

    /** @var string Routing key */
    private $key = null;

    /** @var mixed[] Extras associated to this gate */
    private $extras = [];

    public function __construct($name, $exchange, $queue)
    {
        $this->name = $name;
        $this->queue = $queue;
        $this->exchange = $exchange;
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

    /**
     * @param string $key Routing key to use
     *
     * @return static
     */
    public function setRoutingKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /** @return string Connection's name to use */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $name Connection's name to use
     *
     * @return static
     */
    public function setConnection($name)
    {
        $this->connection = $name;

        return $this;
    }

    /** @return mixed[] */
    public function getExtras()
    {
        return $this->extras;
    }

    /**
     * @param array $extras Extra parameters associated to this gate
     *
     * @return static
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;

        return $this;
    }
}

