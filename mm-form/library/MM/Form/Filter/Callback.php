<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Filter;

use MM\Form\Exception;
use MM\Form\FilterInterface;

/**
 * Toto je wrapper nad callbackom na rychle veci ktore nepotrebuju vlastny klass
 * a najma tym tiez uspokojime typ.
 */
class Callback implements FilterInterface
{
    protected $_callback;

    /**
     * @param $callback
     * @throws Exception
     */
    public function __construct(\Closure $callback)
    {
        $this->_callback = $callback;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function filter($value)
    {
        return call_user_func_array($this->_callback, array($value));
    }
}