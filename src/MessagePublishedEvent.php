<?php
namespace Wisembly\AmqpBundle;

use Datetime;

use Symfony\Component\EventDispatcher\Event;

use Swarrot\Broker\Message as SwarrotMessage;

use Wisembly\AmqpBundle\Gate;

/**
 * Represents a message sent to the broker
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class MessagePublishedEvent extends Event
{
    const NAME = 'message.published';

    /** @var SwarrotMessage */
    private $message;

    /** @var Datetime */
    private $publishedAt;

    /** @var Gate */
    private $gate;

    public function __construct(SwarrotMessage $message, Datetime $publishedAt, Gate $gate)
    {
        $this->gate        = $gate;
        $this->message     = $message;
        $this->publishedAt = $publishedAt;
    }

    public function getMessage(): SwarrotMessage
    {
        return $this->message;
    }

    public function getGate(): Gate
    {
        return $this->gate;
    }

    public function getPublishedAt(): Datetime
    {
        return $this->publishedAt;
    }
}

