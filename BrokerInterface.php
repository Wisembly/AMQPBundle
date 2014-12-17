<?php
namespace Wisembly\AmqpBundle;

use Swarrot\Broker\MessageProvider\MessageProviderInterface,
    Swarrot\Broker\MessagePublisher\MessagePublisherInterface;

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
    /**
     * Return the provider $name for the connection $connection
     *
     * @param string $name Provider's identifier
     *
     * @return MessageProviderInterface
     */
    public function getProvider(Gate $gate);

    /**
     * Return the producer $name for the connection $connection
     *
     * @param string $name Producer's identifier
     *
     * @return MessagePublisherInterface
     */
    public function getProducer(Gate $gate);

    /**
     * Create a temporary queue
     *
     * @param Gate original from which the data will be copied
     *
     * @return Gate the new associated gate
     */
    public function createTemporaryQueue(Gate $origin);
}
