<?php
/**
 * Generic magic model class. Intentionally abstract.
 * Method names as set* or get* should be avoided if possible to reduce the risk
 * of setter/getter naming colision
 *
 * @author Marian Meres
 */
namespace MM\Model;


abstract class AbstractModel
{
    /**
     * Defined fields (and their initial values)
     * @var array
     */
    protected $_data = array();

    /**
     * Array of dirty field names
     * @var array
     */
    protected $_dirty = array();

    /**
     * Bool flag indicating whether or not to watch the dirty fields
     * @var bool
     */
    protected $_watchDirty = true;

    /**
     * @var bool
     */
    protected $_useUndefinedPropertySetHandler = true;

    /**
     * @var string
     */
    protected $_exceptionClass = "MM\Model\Exception";

    /**
     * @param array $data
     * @param bool $forceDirty
     */
    public function __construct(array $data = null, $forceDirty = false)
    {
        $this->_init(); // before data is set

        if ($data) {
            // make sure we're not watching dirt at constructor level... that
            // would make no sense, as everything would always be dirty
            $bkp = $this->_watchDirty;
            $this->_watchDirty = false;
            $this->set($data);
            $this->_watchDirty = $bkp;
        }

        if ($forceDirty) {
            $this->markDirty();
        }
    }

    /**
     * Init hook - intended for default values setup...
     */
    protected function _init() {}

    /**
     * To validate overall model state. Formal property validation should go to
     * relevant setters
     */
    public function validate() {}

    /**
     * Enable/disable undefined property set handler
     *
     * @param bool $flag
     * @return $this
     */
    public function useUndefinedPropertySetHandler($flag = true)
    {
        $this->_useUndefinedPropertySetHandler = (bool) $flag;
        return $this;
    }

    /**
     * More human friendly name alias
     *
     * @param bool $flag
     * @return $this
     */
    public function ignoreUndefinedPropertySetCheck($flag = true)
    {
        return $this->useUndefinedPropertySetHandler($flag);
    }

    /**
     * Enable/disable watching dirty.
     *
     * @param bool $flag
     * @return $this
     */
    public function watchDirty($flag = true)
    {
        $this->_watchDirty = (bool) $flag;
        return $this;
    }

    /**
     * Marks member(s) as dirty
     *
     * @param bool $keys
     * @return $this
     * @throws Exception
     */
    public function markDirty($keys = true)
    {
        if (!$this->_watchDirty) {
            return $this;
        }

        // true means make every member dirty
        if (true === $keys) {
            $keys = array_keys($this->_data);
        }

        foreach ((array) $keys as $key) {
            if (!array_key_exists($key, $this->_data)) {
                throw new Exception(
                    "'$key' key to be marked as dirty was not found"
                );
            }
            $this->_dirty[$key] = $key;
        }

        return $this;
    }

    /**
     * Mark member(s) as clean
     *
     * @param bool $keys
     * @return $this
     * @throws Exception
     */
    public function markClean($keys = true)
    {
        // null means make every member clean
        if (true === $keys) {
            $this->_dirty = array();
            return $this;
        }

        foreach ((array) $keys as $key) {
            if (!array_key_exists($key, $this->_data)) {
                throw new Exception(
                    "'$key' key to be marked as clean was not found"
                );
            }
            unset($this->_dirty[$key]);
        }

        return $this;

    }

    /**
     *
     */
    protected function _preDirtyHook() {}

    /**
     * Intentionally not named "getDirty"
     * @return array
     */
    public function dirtyKeys()
    {
        $this->_preDirtyHook();
        return $this->_dirty;
    }

    /**
     * @return array
     */
    public function dirtyData()
    {
        $this->_preDirtyHook();
        $dirty = array();
        foreach($this->_dirty as $key) {
            $dirty[$key] = $this->$key; // will force getter
        }
        return $dirty;
    }

    /**
     * @param null $key
     * @return bool
     * @throws Exception
     */
    public function isDirty($key = null)
    {
        $this->_preDirtyHook();
        if (null === $key) {
            return !empty($this->_dirty);
        }

        if (!isset($this->$key)) {
            throw new Exception("'$key' key was not found");
        }

        return isset($this->_dirty[$key]);
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->_data);
    }

    /**
     * @param $name
     * @throws Exception
     */
    public function __unset($name)
    {
        throw new Exception("Unsetting is not allowed on " . get_class($this));
    }

    /**
     * @param array $data
     * @return $this
     */
    public function set(array $data)
    {
        foreach ($data as $k => $v) {
            $this->__set($k, $v);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value)
    {
        // if setter exists use it, and return early
        // magic: $name don't necessarily have to be _data member
        $setter = 'set' . ucfirst($name);

        if (method_exists($this, $setter)) {
            // NOTE: custom setters must watch dirt manually...
            return $this->$setter($value);
        }

        // known field
        if ($this->__isset($name)) {
            return $this->_setRawValueAndMarkDirtyIfNeeded($name, $value);
        }

        if ($this->_useUndefinedPropertySetHandler) {
            $this->_undefinedPropertySetHandler($name, $value);
        }

        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function __get($name)
    {
        // if getter exists use it
        // magic: $name don't necessarily have to be _data member
        $getter = 'get' . ucfirst($name);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        if ($this->__isset($name)) {
            return $this->_data[$name];
        }

        return $this->_undefinedPropertyGetHandler($name);
    }

    /**
     * To be overidden if needed...
     *
     * @param $name
     * @param $value
     * @throws Exception
     */
    protected function _undefinedPropertySetHandler($name, $value)
    {
        throw new Exception(sprintf(
            "Setting undefined property '$name' for '%s' is not allowed",
            get_class($this)
        ));
    }

    /**
     * @param $name
     * @return $this
     */
    protected function _undefinedPropertyGetHandler($name)
    {
        // by default, just simulate php's strict behaviour
        trigger_error("Undefined property '$name'", E_USER_NOTICE);

        return $this;
    }


    /**
     * Dumps data as array, forcing getters
     * @return array
     */
    public function toArray()
    {
        $out = array();
        foreach ((array) $this->_data as $k => $v) {
            $out[$k] = $this->__get($k); // force getters
        }
        return $out;
    }

    /**
     * Dumps data as array, forcing getters, but returns only keys which value
     * is different than default (which would mostly mean it would skip nulls).
     * Idea is to have smaller footprints for serialization.
     *
     * @param array $defaultsToSkip
     * @return array
     */
    public function toArraySkipDefaults(array $defaultsToSkip = null)
    {
        if (null === $defaultsToSkip) {
            $class    = get_class($this);
            $default  = new $class;
            $defaultsToSkip = $default->toArray();
        }

        $out = array();
        foreach ((array) $this->_data as $k => $v) {
            $value = $this->__get($k); // force getters
            if (!array_key_exists($k, $defaultsToSkip) || $defaultsToSkip[$k] !== $value) {
                $out[$k] = $value;
            }
        }
        return $out;
    }

    /**
     * Dumps raw data, mainly for debugging purposes. Note that raw data may not
     * be the same as toArray() output (which forces getters)
     *
     * @return array
     */
    public function dump()
    {
        return array(
            '_data'  => $this->_data,
            '_dirty' => $this->_dirty,
        );
    }

    /**
     * DRY helper for common use-case
     * Intentionally not comparing strictly by default (usually data from db (PDO)
     * are not type safe anyway)
     *
     * @param $key
     * @param $value
     * @param bool $strict
     * @return $this
     */
    protected function _setRawValueAndMarkDirtyIfNeeded($key, $value, $strict = false)
    {
        if (($strict && $this->_data[$key] !== $value)
            || (!$strict && $this->_data[$key] != $value)
        ) {
            $this->_data[$key] = $value;
            $this->markDirty($key);
        }
        return $this;
    }
}