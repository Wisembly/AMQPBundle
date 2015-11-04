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
    const IGNORE_ON_INVALID = 0;
    const EXCEPTION_ON_INVALID = 1;

    private $gates = [];

    /** Add a Gate within this Bag */
    public function add(Gate $gate)
    {
        $this->gates[$gate->getName()] = $gate;
    }

    /**
     * Get a Gate from this Bag
     *
     * @param string $gate Gate to fetch
     *
     * @return Gate
     * @throws OutOfBoundsException Gate not found
     */
    public function get($gate)
    {
        if ($this->has($gate)) {
            return $this->gates[$gate];
        }

        throw new OutOfBoundsException(sprintf('The gate "%s" is not within this Bag. Available ones are "%s"', $gate, implode(', ', array_keys($this->gates))));
    }

    /**
     * Checks if a Gate is within this Bag
     *
     * @param string $gate Gate name to search
     * @return bool
     */
    public function has($gate)
    {
        return array_key_exists($gate, $this->gates);
    }

    /** {@inheritDoc} */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /** {@inheritDoc} */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /** {@inheritDoc} */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /** {@inheritDoc} */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('The unset method is not supported on a Bag');
    }
}

