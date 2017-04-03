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

    /** @var array */
    private $exchangeOptions;

    /** @var string Queue to use */
    private $queue;

    /** @var array */
    private $queueOptions;

    /** @var Connection Connection to use */
    private $connection;

    /** @var string Routing key */
    private $key = null;

    /** @var boolean Should we declare queue and exchange befor use */
    private $autoDeclare;

    /**
     * @param string $name Gate's name
     * @param string $exchange Exchange's name
     * @param string $queue Queue's name
     * @param string|null $key routing key to use
     */
    public function __construct(
        Connection $connection,
        $name,
        $exchange,
        $queue,
        $key = null,
        $autoDeclare = true,
        array $queueOptions = [],
        array $exchangeOptions = []
    ) {
        $this->key = $key;
        $this->name = $name;
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->connection = $connection;
        $this->autoDeclare = $autoDeclare;
        $this->queueOptions = $queueOptions;
        $this->exchangeOptions = $exchangeOptions;
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

    /** @return array */
    public function getQueueOptions()
    {
        return $this->queueOptions;
    }

    /** @return array */
    public function getExchangeOptions()
    {
        return $this->exchangeOptions;
    }

    /** @return boolean */
    public function getAutoDeclare()
    {
        return $this->autoDeclare;
    }
}
