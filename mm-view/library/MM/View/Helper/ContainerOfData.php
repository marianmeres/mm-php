<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class ContainerOfData extends Helper implements \Countable
{
    /**
     * @var array
     */
    protected $_container = [];

    /**
     * To be extended. Default noop.
     * @param $data
     * @return mixed
     */
    protected function _validateAndNormalizeData($data)
    {
        return $data;
    }

    /**
     * @param $data
     * @return $this
     */
    public function append($data)
    {
        $data = $this->_validateAndNormalizeData($data);
        $this->_container[] = $data;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function prepend($data)
    {
        $data = $this->_validateAndNormalizeData($data);
        array_unshift($this->_container, $data);
        return $this;
    }

    /**
     * @param array $container
     * @return $this
     */
    public function setContainer(array $container)
    {
        $this->_container = $container;
        return $this;
    }

    /**
     * @return array
     */
    public function getContainer()
    {
        return $this->_container;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_container);
    }

    /**
     * @return $this
     */
    public function reverse()
    {
        $this->_container = array_reverse($this->_container);
        return $this;
    }

    /**
     * To be overridden
     */
    public function toString()
    {
        return print_r($this->_container, true);
    }

    /**
     * to avoid ambiguos "method __toString cannot throw exceptions" use
     * the above toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
