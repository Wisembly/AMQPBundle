<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\DataCollector;

use DatetimeInterface;

use Swarrot\Broker\Message;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\Connection;

final class CollectedData
{
    /** @var Gate */
    private $gate;

    /** @var Message */
    private $message;

    /** @var DatetimeInterface */
    private $publishedAt;

    public function __construct(Gate $gate, Message $message, DatetimeInterface $publishedAt)
    {
        $this->gate = $gate;
        $this->message = $message;
        $this->publishedAt = $publishedAt;
    }

    public function getGate(): Gate
    {
        return $this->gate;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getPublishedAt(): DatetimeInterface
    {
        return $this->publishedAt;
    }

    public function getConnection(): Connection
    {
        return $this->gate->getConnection();
    }

    public function getExchange(): string
    {
        $routingKey = null === $this->gate->getRoutingKey() ? '' : " : {$this->gate->getRoutingKey()}";

        return "{$this->gate->getExchange()}{$routingKey}";
    }
}
