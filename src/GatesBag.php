<?php
namespace Wisembly\AmqpBundle;

use ArrayAccess;

use OutOfBoundsException;
use BadMethodCallException;

/**
 * Gate Bag, contains all the registered gates for this bundle
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class GatesBag implements ArrayAccess
{
    private $gates = [];

    public function add(Gate $gate): void
    {
        $this->gates[$gate->getName()] = $gate;
    }

    public function get(string $gate): Gate
    {
        if ($this->has($gate)) {
            return $this->gates[$gate];
        }

        throw new OutOfBoundsException(sprintf('The gate "%s" is not within this Bag. Available ones are "%s"', $gate, implode(', ', array_keys($this->gates))));
    }

    public function has(string $gate): bool
    {
        return array_key_exists($gate, $this->gates);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset): Gate
    {
        return $this->get($offset);
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value): void
    {
        throw new BadMethodCallException('The set method is not supported on a Bag');
    }

    /** {@inheritDoc} */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset): void
    {
        throw new BadMethodCallException('The unset method is not supported on a Bag');
    }
}
