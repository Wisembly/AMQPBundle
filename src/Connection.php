<?php
namespace Wisembly\AmqpBundle;

/**
 * Value object for an AMQP connection information
 *
 * @author Baptiste Clavié <baptiste@eisembly.com>
 */
class Connection
{
    /** @var string */
    private $name;

    /** @var string */
    private $host;

    /** @var integer */
    private $port;

    /** @var string */
    private $login;

    /** @var string */
    private $password;

    /** @var string */
    private $vhost;

    public function __construct($name, $host, $port, $login, $password, $vhost)
    {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->vhost = $vhost;
    }

    /** @return string */
    public function getName()
    {
        return $this->name;
    }

    /** @return string */
    public function getHost()
    {
        return $this->host;
    }

    /** @return string */
    public function getPort()
    {
        return $this->port;
    }

    /** @return string */
    public function getLogin()
    {
        return $this->login;
    }

    /** @return string */
    public function getPassword()
    {
        return $this->password;
    }

    /** @return string */
    public function getVhost()
    {
        return $this->vhost;
    }
}

