<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 12:10
 */

namespace MM\ContentComponent;

use MM\Model\AbstractModel;

class Model extends AbstractModel
{
    /**
     * Optional meta info (k=>v) describing what kind of content component this is
     * (e.g. type=page|comment, whatever=else)
     * may not be supported for all daos
     *
     * @var array
     */
    public $componentAttributes = [];

    /**
     * @var array
     */
    protected $_data = [
        'id' => null, // component id
    ];

    /**
     * Optional data meta information (think of xml node attributes)
     * @var array
     */
    protected $_dataAttributes = [];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_data['id'];
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->_setRawValueAndMarkDirtyIfNeeded('id', $id);
    }

    /**
     * Override default model behavior: allow to set unknown keys
     * @param $name
     * @param $value
     * @return $this
     */
    public function __set($name, $value)
    {
        if (!$this->__isset($name)) {
            $this->_data[$name] = null;
        }

        return parent::__set($name, $value);
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function initDataAttributes(array $attributes)
    {
        foreach($attributes as $key => $attrs) {
            if (is_array($attrs) && !empty($attrs)) {
                $this->_dataAttributes[$key] = $attrs;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function attrs()
    {
        return $this->_dataAttributes;
    }

    /**
     * Get/Set data attribute
     * @param $property
     * @param null $attrVal
     * @return $this|null|array
     */
    public function attr($property, $attrName = null, $attrVal = null)
    {
        // get per property
        if ($attrVal === null) {

            // get all property attrs
            if ($attrName === null) {
                return (
                    array_key_exists($property, $this->_dataAttributes)
                        ? $this->_dataAttributes[$property] : null
                );
            }

            // get one property attr
            if (isset($this->_dataAttributes[$property][$attrName])) {
                return $this->_dataAttributes[$property][$attrName];
            }

            return null;
        }

        // set
        if ($attrName !== null && $attrVal !== null) {
            $this->_dataAttributes[$property]["$attrName"] = $attrVal;
        }

        return $this;
    }

    /**
     * No need for warnings here... just silence
     * @param $name
     * @return null
     */
    protected function _undefinedPropertyGetHandler($name)
    {
        return null;
    }
}