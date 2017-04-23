<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

use MM\Util\ClassUtil;

class Identical extends AbstractValidator
{
    /**
     * @var string
     */
    protected $_message = "__form_not_identical";

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_not_identical' => "Value '{{value}}' is not identical to '%s'",
    );

    /**
     * @var
     */
    protected $_reference;

    /**
     * @param $value
     * @param array $options
     */
    public function __construct($value, array $options = array())
    {
        $this->_reference = $value;
        ClassUtil::setOptions($this, $options);
    }

    /**
     * @return mixed|string
     */
    public function getMessage()
    {
        return sprintf(parent::getMessage(), $this->_reference);
    }

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $this->_value = $value;
        return $value == $this->_reference;
    }
}