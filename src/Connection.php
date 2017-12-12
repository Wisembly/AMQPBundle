<?php
namespace Wisembly\AmqpBundle;

/**
 * Value object for an AMQP connection information
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
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

    /** @var string */
    private $query;

    public function __construct(
        string $name,
        string $host,
        ?int $port,
        ?string $login,
        ?string $password,
        ?string $vhost,
        ?string $query
    ) {
        $this->name = $name;
        $this->host = $host;
        $this->port = $port;
        $this->login = $login;
        $this->password = $password;
        $this->vhost = $vhost;
        $this->query = $query;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): ?int
    {
        return $this->port;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getVhost(): ?string
    {
        return $this->vhost;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }
}
