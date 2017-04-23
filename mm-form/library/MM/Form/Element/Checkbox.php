<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Validator;
use MM\Form\Element;
use MM\Util\Html;

/**
 * Class Checkbox
 * @package MM\Form\Element
 */
class Checkbox extends Element
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'checkbox'
    );

    /**
     * @var bool
     */
    protected $_useHidden = true;

    /**
     * @var string
     */
    protected $_checkedValue = '1';

    /**
     * @var string
     */
    protected $_uncheckedValue = '0';

    /**
     * @param bool $flag
     * @return $this
     */
    public function setUseHidden($flag = true)
    {
        $this->_useHidden = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseHidden()
    {
        return $this->_useHidden;
    }

    /**
     * @param $v
     * @return $this
     */
    public function setCheckedValue($v)
    {
        $this->_checkedValue = $v;
        return $this;
    }

    /**
     * @return string
     */
    public function getCheckedValue()
    {
        return $this->_checkedValue;
    }

    /**
     * @param $v
     * @return $this
     */
    public function setUncheckedValue($v)
    {
        $this->_uncheckedValue = $v;
        return $this;
    }

    /**
     * @return string
     */
    public function getUncheckedValue()
    {
        return $this->_uncheckedValue;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        parent::setValue($value);

        if ($value != $this->_checkedValue && $value != $this->_uncheckedValue) {
            $this->_value = $this->_uncheckedValue;
        }

        return $this;
    }

    /**
     * @param null $context
     */
    protected function _validate($context = null)
    {
        if ($this->_required) {
            $this->addValidator(
                new Validator\Identical($this->_checkedValue, array(
                    // 'message' => 'Checkbox is required to be checked'
                    'message' => '__form_checkbox_not_checked',
                    'messageTemplates' => array(
                        '__form_checkbox_not_checked' => 'Checkbox is required to be checked',
                    ),
                ))
            );
        }
        parent::_validate($context);
    }

    /**
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {
        // merge provided options with defaults
        $o = array_merge([
            'escape_label' => true,
            'render_errors' => true,
        ], $options);

        // ak je string, tak to chapeme ako forced error mesasge
        $forcedErrorMsg = null;
        if (is_string($o['render_errors'])) {
            $forcedErrorMsg = $o['render_errors'];
        }

        $data = $this->getRenderData('');
        $label = $o['escape_label'] ? htmlspecialchars($this->getLabel()) : $this->getLabel();
        $errs = $o['render_errors'] ? $this->renderErrors($forcedErrorMsg) : '';
        $mmType = !empty($data['type']) ? "mmfe-$data[type]" : "";
        $class = trim("$mmType $data[required_cls] $data[needfix_cls]");

        // <div class="checkbox">
        //     <label>
        //         <input type="checkbox"> Check me out
        //     </label>
        // </div>

        $out = "<div class='" . trim("checkbox $class") . "'>"
             .   "\n  <label class='$class'>"
             .     "\n    " . $this->renderCoreTag() . " &nbsp;$label"
             .   "\n  </label>"
             .   ('' == $errs ? "" : "\n  $errs")
             . "\n</div>\n";

        return $out;
    }

    /**
     * @return string
     */
    public function renderCoreTag(array $customAttrs = [])
    {
        $attrs = $this->getAttributes();
        $attrs['value'] = $this->getCheckedValue();
        $attrs['name']  = $this->getName();

        if ($this->getCheckedValue() == $this->getValue()) {
            $attrs['checked'] = 'checked';
        }

        // <input type="checkbox" name="vehicle" value="Car">I have a car

        $out = '';

        // tu najskor hidden s nechecked hodnotou
        if ($this->_useHidden) {
            $out = Html::renderTag('input', array(
                'type'  => 'hidden',
                'name'  => $this->getName(),
                'value' => $this->getUncheckedValue()
            ));
        }

        // required?
        if ($this->isRequired()) {
            $attrs['required'] = 'required';
        }

        $attrs = array_merge($attrs, $customAttrs);

        // a az nasledne skutocnu checkbox
        $out .= Html::renderTag('input', $attrs);

        // vyssi hidden+normal budu mat za nasledok, ze hodnota bude v parametroch
        // poslana z formu dva krat, ale myslim, ze je bezpecne ratat s tym, ze
        // za kazdych okolnosti neskorsi vyhrava...

        return $out;
    }

}
