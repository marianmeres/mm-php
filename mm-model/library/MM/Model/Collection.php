<?php
/**
 * @author Marian Meres
 */
namespace MM\Model;

/**
 * very basic collection
 *
 * Class Collection
 * @package MM\Model
 */
class Collection implements \Countable, \ArrayAccess, \IteratorAggregate
{
    /**
     * Fully quallified class name of the allowed domain class in this collection
     *
     * @var string
     */
    protected $_domainClass = '\MM\Model\AbstractModel';

    /**
     * Entities in this collection
     *
     * @var array
     */
    protected $_entities = array();

    /**
     * Dirt flag (as opposed to models, collection can be explicitly flagged
     * as dirty).
     *
     * All values (true/false/null) are significant:
     *  true  - explicitelly marked as dirty (will not check child models)
     *  false - explicitelly marked as clean (will not check child models)
     *  null  - will iterate models to find out
     *
     * @var bool|null
     */
    protected $_isDirty;

    /**
     * @param null $domainClass
     * @throws Exception
     */
    public function __construct($domainClass = null)
    {
        if ($domainClass) {
            $this->_domainClass = $domainClass;
        }

        if (null === $this->_domainClass) {
            throw new Exception("You must provide the domain class name");
        }
    }

    /**
     * @return null|string
     */
    public function getDomainClass()
    {
        return $this->_domainClass;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_entities);
    }

    /**
     * @param $model
     * @param bool $throw
     * @return bool
     * @throws Exception
     */
    public function accepts($model, $throw = false)
    {
        if (is_object($model) && ($model instanceof $this->_domainClass)) {
            return true;
        }

        if ($throw) {
            $msg = get_class($this)
                 . ": Expecting instance of '{$this->_domainClass}'";

            if (is_object($model)) {
                $msg .= sprintf(", but '%s' was provided", get_class($model));
            }

            throw new Exception($msg);
        }

        return false;
    }

    /**
     * @param $entity
     * @throws Exception
     */
    protected function _assertValidInstace($entity)
    {
        $this->accepts($entity, $throw = true);
    }

    /**
     *
     */
    public function clear()
    {
        $this->_entities = array();
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_entities);
    }

    /**
     * @see \ArrayAccess
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_entities);
    }

    /**
     * @see \ArrayAccess
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->_entities[$offset];
    }

    /**
     * @see \ArrayAccess
     * @param mixed $offset
     * @param mixed $entity
     */
    public function offsetSet($offset, $entity)
    {
        $this->_assertValidInstace($entity);

        if ($offset === null) {
            $this->_entities[] = $entity;
        } else {
            $this->_entities[$offset] = $entity;
        }
    }

    /**
     * @see \ArrayAccess
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_entities[$offset]);
    }

    /**
     * @param array $entities
     * @param bool $reset
     * @return $this
     */
    public function add(array $entities, $reset = false)
    {
        if ($reset) {
            $this->clear();
        }
        foreach ($entities as $entity) {
            $this[] = $entity;
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_entities);
    }

    /**
     * @param $entity
     * @return $this
     */
    public function unshift($entity)
    {
        $this->_assertValidInstace($entity);
        array_unshift($this->_entities, $entity);
        return $this;
    }

    /**
     * @param $model
     * @param bool $strict
     * @return bool
     * @throws Exception
     */
    public function contains($model, $strict = false)
    {
        if (!is_object($model)) {
            throw new Exception("Invalid argument. Expecting object instance.");
        }

        if (!$this->accepts($model)) {
            throw new Exception(
                "Invalid argument; not supported instance " . get_class($model)
              . ". Accepting only " . $this->_domainClass
            );
        }

        // intentionally not foreach
        for ($i = 0, $count = count($this->_entities); $i < $count; $i++) {
            $_model = $this->_entities[$i];
            if (($strict && $model === $_model) || (!$strict && $model == $_model)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return $this
     */
    public function shuffle()
    {
        shuffle($this->_entities);
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isDirty()
    {
        // 1. explicit flag? (highest priority)
        if (is_bool($this->_isDirty)) {
            return $this->_isDirty;
        }

        // 2. iterate models
        foreach ($this as $model) {
            if ($model instanceof AbstractModel && $model->isDirty()) {
                return true;
            }
        }

        // 3. no dirt found
        return false;
    }

    /**
     * sets explicit dirt flag (no models check)
     * @return $this
     */
    public function markDirty()
    {
        $this->_isDirty = true;
        return $this;
    }

    /**
     * unsets explicit dirt flag (no models check)
     * @return $this
     */
    public function markClean()
    {
        $this->_isDirty = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function resetDirtyMark()
    {
        $this->_isDirty = null;
        return $this;
    }
}
