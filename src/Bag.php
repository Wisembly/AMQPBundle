<?php
namespace Wisembly\AmqpBundle;

use InvalidArgumentException;

use Wisembly\AmqpBundle\Exception\MalformedPathException;
use Wisembly\AmqpBundle\Exception\ParameterNotFoundException;

/**
 * Gate Bag, contains all the registered gates for this bundle
 *
 * Acts as a stateful service, and was heavily based on Symfony's
 * HTTP Foundation's ParameterBag, with some stripped methods.
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class Bag
{
    /** @var array */
    private $parameters = [];

    /** @var array */
    private $cache = [];

    /** @var array */
    private $defaults = [];

    public function __construct(array $parameters = [])
    {
        $this->defaults   = $parameters;
        $this->parameters = $parameters;
    }

    /**
     * Get all the elements in the array
     *
     * @return array
     */
    public function all()
    {
        return $this->parameters;
    }

    /** Resets the content of the bag to its default */
    public function reset()
    {
        $this->cache      = [];
        $this->parameters = $this->defaults;

        return $this;
    }

    /**
     * Return the defaults of this bag
     *
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * Sets the default values for this bag
     *
     * This will be used whenever we need to deal with a reset of this bag
     *
     * @param array $defaults
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;

        return $this;
    }

    /**
     * Get an element represented by a key, or return the default if not found
     *
     * If the deep mode is activated, it will try to interpret the key argument
     * as a nested key. If not, the key will be searched as is.
     *
     * @param mixed   $path    Key to get
     * @param mixed   $default Default value to return if not found
     * @param boolean $deep    If true, will search a path like foo[bar] deeper
     *
     * @return mixed
     * @throws MalformedPathException If the path is malformed
     */
    public function get($path, $default = null, $deep = false)
    {
        if (!$deep) {
            return $this->has($path, false) ? $this->parameters[$path] : $default;
        }

        return $this->has($path, true) ? $this->translatePath($path) : $default;
    }

    /**
     * Set an element designed by a key
     *
     * @param mixed $key   Key to set
     * @param mixed $value Value to set
     *
     * @return self
     */
    public function set($key, $value)
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * Remove a root element from the stash
     *
     * @param mixed $key Key to remove
     * @return self
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            unset($this->parameters[$key]);
        }

        return $this;
    }

    /**
     * Checks the existence of a key in this bag
     *
     * If the deep mode is activated, it will try to interpret the key argument
     * as a nested key. If not, the key will be searched as is.
     *
     * @param mixed   $path Key to search
     * @param boolean $deep If true, will search paths such as foo[bar]
     *
     * @return Boolean true if found, false otherwise
     * @throws MalformedPathException If the path is malformed
     */
    public function has($path, $deep = false)
    {
        if (!$deep) {
            return array_key_exists($path, $this->parameters);
        }

        try {
            $this->translatePath($path);
            return true;
        } catch (ParameterNotFoundException $e) {
            return false;
        }
    }

    /**
     * Translate a path foo[bar] into its value
     *
     * This method was adapted from Symfony's HTTP Foundation's ParameterBag's
     * get method.
     *
     * @param mixed $path Path to the value
     *
     * @return mixed Computed value
     * @throws ParameterNotFoundException
     * @throws MalformedPathException
     */
    private function translatePath($path)
    {
        if (array_key_exists($path, $this->cache)) {
            return $this->cache[$path];
        }

        if (false === $pos = strpos($path, '[')) {
            if (!$this->has($path)) {
                throw new ParameterNotFoundException($path);
            }

            return $this->cache[$path] = $this->parameters[$path];
        }

        $root = substr($path, 0, $pos);

        if (!$this->has($root)) {
            throw new ParameterNotFoundException($path);
        }

        $key   = null;
        $value = $this->parameters[$root];

        for ($i = $pos, $c = strlen($path); $i < $c; ++$i) {
            switch ($path[$i]) {
                case '[':
                    // nested keys not supported
                    if (null !== $key) {
                        throw new MalformedPathException($path[$i], $i);
                    }

                    $key = '';
                    break;

                case ']':
                    // not in a key
                    if (null === $key) {
                        throw new MalformedPathException($path[$i], $i);
                    }

                    if (!is_array($value) || !array_key_exists($key, $value)) {
                        throw new ParameterNotFoundException($path);
                    }

                    $value = $value[$key];
                    $key   = null;
                    break;

                default:
                    // not in a key
                    if (null === $key) {
                        throw new MalformedPathException($path[$i], $i);
                    }

                    $key .= $path[$i];
                    break;
            }
        }

        if (null !== $key) {
            throw new MalformedPathException(null, null, 'a path must end with a "]"');
        }

        return $this->cache[$path] = $value;
    }
}

