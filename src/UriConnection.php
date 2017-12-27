<?php
namespace Wisembly\AmqpBundle;

use InvalidArgumentException;

/**
 * Value object representing an URI connection
 *
 * @link https://www.rabbitmq.com/uri-spec.html
 * @author Baptiste Clavié <clavie.b@gmail.com>
 */
class UriConnection extends Connection
{
    public function __construct(string $name, string $uri)
    {
        $parse = $this->parseUri($uri);

        parent::__construct(
            $name,
            $parse['host'],
            $parse['port'],
            $parse['login'],
            $parse['password'],
            $parse['vhost'],
            $parse['query']
        );
    }

    private function parseUri(string $uri): array
    {
        if (false === $parse = parse_url($uri)) {
            throw new InvalidArgumentException('Could not parse uri');
        }

        if (!isset($parse['scheme'])) {
            throw new InvalidArgumentException('Missing scheme.');
        }

        if (!in_array(strtolower($parse['scheme']), ['amqp', 'amqps'])) {
            throw new InvalidArgumentException("Invalid scheme. Expected 'amqp(s)', had '{$parse['scheme']}'");
        }

        if (isset($parse['path'], $parse['path'][0]) && '/' === $parse['path'][0]) {
            $parse['path'] = substr($parse['path'], 1);
        }

        return [
            'host' => $parse['host'],
            'port' => $parse['port'] ?? null,
            'login' => $parse['user'] ?? null,
            'password' => $parse['pass'] ?? null,
            'vhost' => $parse['path'] ?? null,
            'query' => $parse['query'] ?? null
        ];
    }
}
