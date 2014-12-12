<?php
namespace Wisembly\AmqpBundle\EventListener;

use Datetime;

use Symfony\Component\EventDispatcher\Event;

use Swarrot\Broker\Message;

/**
 * Represents a message sent to the broker
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class MessagePublishedEvent extends Event
{
    const NAME = 'message.published';

    /** @var Message */
    private $message;

    /** @var Datetime */
    private $publishedAt;

    /** @var string */
    private $gate;

    /** @var string[] */
    private $config;

    public function __construct(Message $message, Datetime $publishedAt, $gate, array $config)
    {
        $this->gate        = $gate;
        $this->config      = $config;
        $this->message     = $message;
        $this->publishedAt = $publishedAt;
    }

    /** @return Message */
    public function getMessage()
    {
        return $this->message;
    }

    /** @return string */
    public function getGate()
    {
        return $this->gate;
    }

    /** @return string[] */
    public function getConfig()
    {
        return $this->config;
    }

    public function getPublishedAt()
    {
        return $this->publishedAt;
    }
}

