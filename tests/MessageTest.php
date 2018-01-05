<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use PHPUnit\Framework\TestCase;
use Swarrot\Broker\Message as SwarrotMessage;

class MessageTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $message = new Message('foo');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertInstanceOf(SwarrotMessage::class, $message);
    }

    public function test_it_is_converted_to_a_swarrot_message()
    {
        $message = new Message('foo');

        $json = <<<'JSON'
{
    "command": "foo",
    "arguments": [],
    "stdin": null
}
JSON;

        $this->assertInstanceOf(SwarrotMessage::class, $message);
        $this->assertJsonStringEqualsJsonString($json, $message->getBody());
    }

    public function test_it_rebuilds_body_if_stdin_changes()
    {
        $message = new Message('foo');
        $message->setStdin('bar');

        $json = <<<'JSON'
{
    "command": "foo",
    "arguments": [],
    "stdin": "bar"
}
JSON;

        $this->assertJsonStringEqualsJsonString($json, $message->getBody());

    }
}
