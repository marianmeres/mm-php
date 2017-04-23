<?php
namespace MM\Mapper\Dao;

use MM\Util\ClassUtil;

/**
 * Class AbstractDao
 * @package MM\ContentComponent\Dao
 */
abstract class AbstractDao
{
    /**
     * Slovo "table" tu moze byt matuce trosku, ale v tejto abstraktnej urovni
     * sa to da chapat ako "entity" name
     *
     * @var string
     */
    public $tableName;

    /**
     * @var bool
     */
    public $autoIncrement = false;

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if ($options) {
            ClassUtil::setOptions($this, $options);
        }
        $this->_init();
    }

    /**
     *
     */
    protected function _init()
    {
    }

    /**
     * quote value
     * @param $val
     * @return string
     */
    public function qv($val)
    {
        return $val;
    }

    /**
     * quote identifier
     * @param $val
     * @return string
     */
    public function qi($val)
    {
        return $val;
    }

    /**
     * Default noop; open to implementations
     * @param array|null $options
     */
    public function begin(array $options = null){}

    /**
     * Default noop; open to implementations
     * @param array|null $options
     */
    public function rollback(array $options = null){}

    /**
     * Default noop; open to implementations
     * @param array|null $options
     */
    public function commit(array $options = null){}

    /**
     * @param array $idData
     * @param array|null $options
     * @return mixed
     */
    abstract public function exists(array $idData, array $options = null);

    /**
     * @param array $data
     * @param array|null $options
     * @return mixed
     */
    abstract public function create(array $data, array $options = null);

    /**
     * @param array $idData
     * @param array|null $options
     * @return mixed
     */
    abstract public function read(array $idData, array $options = null);

    /**
     * @param mixed $where
     * @param array|null $options
     * @return mixed
     */
    abstract public function fetchAll($where, array $options = null);

    /**
     * @param array $idData
     * @param $data
     * @param array|null $options
     * @return mixed
     */
    abstract public function update(array $idData, $data, array $options = null);

    /**
     * @param array $idData
     * @param array|null $options
     * @return mixed
     */
    abstract public function delete(array $idData, array $options = null);
}