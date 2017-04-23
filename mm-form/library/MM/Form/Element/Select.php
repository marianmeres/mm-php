<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Util\Html;

class Select extends AbstractMulti
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'select'
    );

    /**
     * @var bool
     */
    protected $_multipleAttr = false;

    /**
     * @param array $customAttrs
     * @return string
     */
    public function renderCoreTag(array $customAttrs = [])
    {
        $multiOptions = $this->getMultiOptions();
        $value = $this->getValue();

        $attrs = $this->getAttributes();
        $attrs['name']  = $this->getName();

        if ($this->_multipleAttr) {
            $attrs['multiple'] = 'multiple';
        }

        if (empty($attrs['class'])) {
            $attrs['class'] = 'form-control'; // bootstraps convention
        }

        $attrs = array_merge($attrs, $customAttrs);

        $out = Html::renderTag('select', $attrs, null, false); // . "\n";

        // DRY helper
        $renderOption = function($value, $label, $selectedValue) {
            $attrs = [];
            if ($selectedValue == $value) {
                $attrs['selected'] = 'selected';
            }
            if ("" != $label) {
                if ($this->_translate) {
                    $label = $this->_translate->translate($label);
                }
                $attrs['value'] = $value;
                $value = $label;
            }
            return Html::renderTag("option", $attrs, $value);// . "\n";
        };

        foreach ($multiOptions as $option => $label) {

            // optgroup
            if (is_array($label)) {
                $optgroup = Html::renderTag('optgroup', array(
                    'label' => $option
                ), null, false); // . "\n";

                foreach ($label as $option2 => $label2) {
                    $optgroup .= $renderOption($option2, $label2, $value);
                }

                $out .= "$optgroup</optgroup>";
            }
            // "regular" options
            else {
                $out .= $renderOption($option, $label, $value);
            }
        }

        $out .= '</select>';

        return $out;
    }
}
