<?php
namespace Wisembly\AmqpBundle;

use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

/**
 * BrokerInterface
 *
 * Represents a broker which will give out the proper provider & producers
 * to interact with RabbitMq
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
interface BrokerInterface
{
    public function getProvider(Gate $gate): MessageProviderInterface;

    public function getProducer(Gate $gate): MessagePublisherInterface;

    public function createTemporaryQueue(Gate $origin): Gate;
}
