<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

use MM\Util\ClassUtil;

class InArray extends AbstractValidator
{
    /**
     * Pole voci ktoremu bude validovane
     * @var array
     */
    protected $_values = array();

    /**
     * @var string
     */
    protected $_message = "__form_value_not_in_array";

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_value_not_in_array' => "Value '{{value}}' is not allowed",
    );

    /**
     * @param array $values
     * @param array $options
     */
    public function __construct(array $values, array $options = array())
    {
        $this->_values = $values;
        ClassUtil::setOptions($this, $options);
    }

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $this->_value = $value;
        return in_array($value, $this->_values);
    }
}