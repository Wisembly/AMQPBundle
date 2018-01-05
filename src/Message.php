<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use Swarrot\Broker\Message as SwarrotMessage;

class Message extends SwarrotMessage
{
    /** @var string Command name to execute */
    private $command;

    /** @var string[] Raw arguments to pass to the command `['-v', '--foo=bar', 'baz']` */
    private $arguments;

    /** @var ?string stdin to use with the command if any */
    private $stdin;

    public function __construct(string $command, array $arguments = [], array $properties = [], $id = null)
    {
        $this->command = $command;
        $this->arguments = $arguments;

        parent::__construct(json_encode(
            [
                'command' => $command,
                'arguments' => $arguments,
                'stdin' => null,
            ]
        ), $properties, $id);
    }

    public function setStdin(?string $stdin): void
    {
        $this->stdin = $stdin;

        $this->body = json_encode(
            [
                'command' => $this->command,
                'arguments' => $this->arguments,
                'stdin' => $stdin
            ]
        );
    }
}
