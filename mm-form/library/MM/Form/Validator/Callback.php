<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

/**
 * Toto je wrapper nad callbackom na rychle veci ktore nepotrebuju vlastny klass
 * a najma tym tiez uspokojime typ.
 */
class Callback extends AbstractValidator
{
    /**
     * @var \Closure
     */
    protected $_callback;

    /**
     * @param callable $callback
     * @param bool $breakFlag
     */
    public function __construct(\Closure $callback, $breakFlag = true) // \Callable od 5.4
    {
        $this->_callback = $callback;
        $this->_breakFlag = (bool) $breakFlag;
    }

    /**
     * @param $value
     * @param null $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $result = call_user_func_array($this->_callback, array($value, $context));
        if ($result !== true) { // ine ako true chapeme ako message
            $this->_message = $result;
            return false;
        }
        return true;
    }
}