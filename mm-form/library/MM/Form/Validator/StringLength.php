<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

class StringLength extends AbstractValidator
{
    /**
     * @var number
     */
    protected $_min;

    /**
     * @var number
     */
    protected $_max; // inclusive

    /**
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_string_short' => "Required length is at least %d characters",
        '__form_string_long' => "Allowed length is at most %d characters",
    );

    /**
     * @var mixed
     */
    protected $_messageValue;

    /**
     * @param int $min
     * @param int $max
     */
    public function __construct($min = 1, $max = 255)
    {
        $this->_min = abs((int) $min);
        $this->_max = abs((int) $max);
    }

    /**
     * @return mixed|string
     */
    public function getMessage()
    {
        return sprintf(parent::getMessage(), $this->_messageValue);
    }

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $len = mb_strlen($value, 'UTF-8');
        $this->_value = $value;

        if ($this->_min && $len < $this->_min) {
            $this->_messageValue = $this->_min;
            $this->_message = "__form_string_short";
            return false;
        }

        if ($this->_max && $len > $this->_max) {
            $this->_messageValue = $this->_max;
            $this->_message = "__form_string_long";
            return false;
        }

        return true;
    }
}