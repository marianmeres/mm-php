<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;

use MM\Util\Html;

class Button extends Element
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'button'
    );

    /**
     * @return string
     */
    public function renderCoreTag(array $customAttrs = [])
    {
        $attrs = $this->getAttributes();

        $attrs = array_merge($attrs, $customAttrs);

        return Html::renderTag(
            'button', $attrs, $this->getValue(), true
        );
    }

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
