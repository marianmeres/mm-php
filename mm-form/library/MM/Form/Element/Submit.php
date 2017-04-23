<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;

class Submit extends Element
{
    protected $_defaultAttributes = array(
        'type' => 'submit'
    );

    /**
     * Pri button sa value preklada
     * @return mixed
     */
    public function getValue()
    {
        if ($this->_translate) {
            return $this->_translate->translate($this->_value);
        }
        return $this->_value;
    }

}