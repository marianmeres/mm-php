<?php
namespace MM\ContentComponent\Dao;

use MM\Util\ClassUtil;

/**
 * Class AbstractDao
 * @package MM\ContentComponent\Dao
 */
abstract class AbstractDao
{
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
     * @param $componentId
     * @param array $options
     * @return mixed
     */
    abstract public function exists($componentId, array $options = null);

    /**
     * @param $componentId
     * @param $data
     * @param array $options
     * @return mixed
     */
    abstract public function create($componentId, $data, array $options = null);

    /**
     * @param $componentId
     * @param array $options
     * @return mixed
     */
    abstract public function read($componentId, array $options = null);

    /**
     * @param $componentId
     * @param $data
     * @param array $options
     * @return mixed
     */
    abstract public function update($componentId, $data, array $options = null);

    /**
     * @param $componentId
     * @param array $options
     * @return mixed
     */
    abstract public function delete($componentId, array $options = null);

    /**
     * Returns array of existing component ids (adapter specific implementation)
     * @param array $options
     * @return array
     */
    abstract public function fetchAvailableComponentIds(array $options = null);
}