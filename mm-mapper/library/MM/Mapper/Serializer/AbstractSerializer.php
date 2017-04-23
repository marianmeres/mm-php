<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 16:10
 */

namespace MM\Mapper\Serializer;

use MM\Util\ClassUtil;

abstract class AbstractSerializer
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
     * @param array $data
     * @param array $options
     * @return mixed
     */
    abstract public function serialize(array $data, array $options = array());

    /**
     * @param $data
     * @param array $options
     * @return mixed
     */
    abstract public function unserialize($data, array $options = array());
}