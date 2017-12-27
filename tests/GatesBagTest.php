<?php declare(strict_types=1);
namespace Wisembly\AmqpBundle;

use PHPUnit\Framework\TestCase;

class GatesBagTest extends TestCase
{
    public function test_the_bag_can_be_instantiated(): void
    {
        $bag = new GatesBag;

        $this->assertInstanceOf(GatesBag::class, $bag);
    }

    public function test_get_valid_gate_returns_gate(): void
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $bag = new GatesBag;
        $bag->add($gate);

        $gate = $bag->get('foo');

        $this->assertInstanceOf(Gate::class, $gate);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage The gate "foo" is not within this Bag. Available ones are ""
     */
    public function test_get_invalid_gate_triggers_exception(): void
    {
        $bag = new GatesBag;
        $bag->get('foo');
    }

    public function test_offset_get_valid_gate_returns_gate(): void
    {
        $connection = new UriConnection('foo', 'amqp://localhost');
        $gate = new Gate($connection, 'foo', 'bar', 'baz');

        $bag = new GatesBag;
        $bag->add($gate);

        $gate = $bag['foo'];

        $this->assertInstanceOf(Gate::class, $gate);
    }

    /**
     * @expectedException OutOfBoundsException
     * @expectedExceptionMessage The gate "foo" is not within this Bag. Available ones are ""
     */
    public function test_offset_get_invalid_gate_triggers_exception(): void
    {
        $bag = new GatesBag;
        $bag['foo'];
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage The set method is not supported on a Bag
     */
    public function test_offset_set_cant_be_called(): void
    {
        $bag = new GatesBag;
        $bag['foo'] = 'bar';
    }

    /**
     * @expectedException BadMethodCallException
     * @expectedExceptionMessage The unset method is not supported on a Bag
     */
    public function test_offset_unset_cant_be_called(): void
    {
        $bag = new GatesBag;
        unset($bag['foo']);
    }
}
