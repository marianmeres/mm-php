<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;
use MM\Util\Html;

class Textarea extends Element
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'textarea'
    );

    /**
     * @return string
     */
    public function renderCoreTag(array $customAttrs = [])
    {
        $attrs = $this->getAttributes();
        $attrs['name']  = $this->getName();

        if (empty($attrs['class'])) {
            $attrs['class'] = 'form-control'; // bootstraps convention
        }

        // required?
        if ($this->isRequired()) {
            $attrs['required'] = 'required';
        }

        $attrs = array_merge($attrs, $customAttrs);

        return Html::renderTag('textarea', $attrs, $this->getValue());
    }
}