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
     * Add a connection to the stack of connections
     *
     * @param string $name       Connection's name
     * @param array  $connection Connection parameters. Should contain the following keys:
     *                              - host, which is the host of the connection
     *                              - port, which is the port to connect to
     *                              - login, the user login
     *                              - password, the user's password
     *                              - vhost, the virtual host to hit
     *
     * @return self
     */
    public function addConnection($name, array $connection);

    /**
     * Return the provider $name for the connection $connection
     *
     * @param string $name       Provider's identifier
     * @param string $connection Connection to use. if null, use the first one
     *
     * @return MessageProviderInterface
     */
    public function getProvider($name, $connection = null);

    /**
     * Return the producer $name for the connection $connection
     *
     * @param string $name       Producer's identifier
     * @param string $connection Connection to use. if null, use the first one
     *
     * @return MessagePublisherInterface
     */
    public function getProducer($name, $connection = null);
}
