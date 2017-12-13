<?php
namespace Wisembly\AmqpBundle;

/**
 * Value object for an AMQP gate
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
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

    /** @var bool Should we declare queue and exchange befor use */
    private $autoDeclare;

    public function __construct(
        Connection $connection,
        string $name,
        string $exchange,
        string $queue,
        ?string $key = null,
        bool $autoDeclare = true,
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

    public function getName(): string
    {
        return $this->name;
    }

    public function getExchange(): string
    {
        return $this->exchange;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getRoutingKey(): ?string
    {
        return $this->key;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    public function getQueueOptions(): array
    {
        return $this->queueOptions;
    }

    public function getExchangeOptions(): array
    {
        return $this->exchangeOptions;
    }

    public function getAutoDeclare(): bool
    {
        return $this->autoDeclare;
    }
}
