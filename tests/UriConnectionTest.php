<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use PHPUnit\Framework\TestCase;

class UriConnectionTest extends TestCase
{
    /** @dataProvider schemeProvider */
    public function test_the_connection_can_be_instantiated($scheme): void
    {
        $connection = new UriConnection('foo', "{$scheme}://foo:bar@localhost:5673/baz?qux=qax");

        $this->assertSame('foo', $connection->getName());
        $this->assertSame('localhost', $connection->getHost());

        $this->assertSame(5673, $connection->getPort());
        $this->assertSame('foo', $connection->getLogin());
        $this->assertSame('qux=qax', $connection->getQuery());
        $this->assertSame('baz', $connection->getVhost());
        $this->assertSame('bar', $connection->getPassword());
    }

    public function schemeProvider(): array
    {
        return [
            'insecure connection' => ['amqp'],
            'secure connection' => ['amqps'],
        ];
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Could not parse uri
     */
    public function test_malformed_url_triggers_exception()
    {
        new UriConnection('foo', 'bar://');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Missing scheme.
     */
    public function test_missing_scheme_triggers_exception()
    {
        new UriConnection('foo', 'bar');
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid scheme. Expected 'amqp(s)', had 'bar'
     */
    public function test_invalid_scheme_triggers_exception()
    {
        new UriConnection('foo', 'bar://foo');
    }
}
