<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle\Processor;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use Swarrot\Consumer;

use Swarrot\Processor\Ack\AckProcessor;
use Swarrot\Processor\RPC\RpcServerProcessor;
use Swarrot\Processor\Stack\StackedProcessor;
use Swarrot\Processor\MemoryLimit\MemoryLimitProcessor;
use Swarrot\Processor\SignalHandler\SignalHandlerProcessor;
use Swarrot\Processor\ExceptionCatcher\ExceptionCatcherProcessor;

use Wisembly\AmqpBundle\Gate;
use Wisembly\AmqpBundle\BrokerInterface;

class ConsumerFactory
{
    /** @var BrokerInterface */
    private $broker;

    /** @var CommandProcessor */
    private $processor;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(?LoggerInterface $logger, BrokerInterface $broker, CommandProcessor $processor)
    {
        $this->broker = $broker;
        $this->processor = $processor;
        $this->logger = $logger ?: new NullLogger;
    }

    public function getConsumer(Gate $gate): Consumer
    {
        $provider = $this->broker->getProvider($gate);
        $producer = $this->broker->getProducer($gate);

        $processor = $this->processor;
        $middlewares = [$this->processor];

        $producer = $this->broker->getProducer($gate);

        $processor = new AckProcessor($processor, $provider, $this->logger);
        $middlewares[] = $processor;

        $processor = new RpcServerProcessor($processor, $producer, $this->logger);
        $middlewares[] = $processor;

        $processor = new MemoryLimitProcessor($processor, $this->logger);
        $middlewares[] = $processor;

        $processor = new ExceptionCatcherProcessor($processor, $this->logger);
        $middlewares[] = $processor;

        if (extension_loaded('pcntl')) {
            $processor = new SignalHandlerProcessor($processor, $this->logger);
            $middlewares[] = $processor;
        }

        $processor = new StackedProcessor($processor, $middlewares);

        return new Consumer($provider, $processor);
    }
}
