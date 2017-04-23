<?php
/**
 * Ported/Inspired from Zend\Stdlib\Parameters
 */

namespace MM\Controller;

/**
 * Class Parameters
 * @package MM\Controller
 */
class Parameters extends \ArrayObject
{
    /**
     * Constructor
     *
     * Enforces that we have an array, and enforces parameter access to array
     * elements.
     *
     * @param array $values
     */
    public function __construct(array $values = null)
    {
        if (null === $values) {
            $values = array();
        }
        parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Populate from native PHP array
     *
     * @param array $values
     * @return $this
     */
    public function fromArray(array $values)
    {
        $this->exchangeArray($values);
        return $this;
    }

    /**
     * Populate from query string
     *
     * @param $string
     * @return $this
     */
    public function fromString($string)
    {
        $array = array();
        parse_str($string, $array);
        $this->fromArray($array);
        return $this;
    }

    /**
     * Serialize to native PHP array
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getArrayCopy();
    }

    /**
     * Serialize to query string
     *
     * @return string
     */
    public function toString()
    {
        return http_build_query($this);
    }

    /**
     * Retrieve by key
     *
     * Returns null if the key does not exist.
     *
     * @param mixed $name
     * @return mixed|null
     */
    public function offsetGet($name)
    {
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
        return null;
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed|null
     */
    public function get($name, $default = null)
    {
        if (isset($this[$name])) {
            return parent::offsetGet($name);
        }
        return $default;
    }

    /**
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function set($name, $value)
    {
        $this[$name] = $value;
        return $this;
    }
}
