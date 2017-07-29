<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 12:04
 */

namespace MM\ContentComponent;

use MM\Util\ClassUtil;

/**
 * Class ContentComponent
 * @package MM\ContentComponent
 *
 * Manager/Service
 */
class Service
{
    /**
     * @var Mapper
     */
    protected $_mapper;

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
     * @param Mapper $mapper
     * @return $this
     */
    public function setMapper(Mapper $mapper = null)
    {
        $this->_mapper = $mapper;
        return $this;
    }

    /**
     * @return Mapper
     * @throws Exception
     */
    public function getMapper()
    {
        if (!$this->_mapper) {
            throw new Exception("Mapper instance not set");
        }
        return $this->_mapper;
    }

    /**
     * Proxy to main package api
     *
     * @param $componentId
     * @param bool $assert
     * @return Model|null
     * @throws Exception
     */
    public function find($componentId, $assert = true)
    {
        return $this->getMapper()->find($componentId, $assert);
    }

    /**
     * Proxy to main package api
     * @param Model $model
     * @return Model
     * @throws Exception
     */
    public function save(Model $model)
    {
        return $this->getMapper()->save($model);
    }
}