<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Exception;
use MM\Util\Html;

class Radio extends AbstractMulti
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'radio'
    );

    /**
     * Custom, special case, "one radio tag" renderer.
     * @param $value
     * @param array $options
     * @return string
     * @throws Exception
     */
//    public function renderOne($value, array $options = [])
//    {
//        $o = array_merge([
//            'escape_label' => true,
//            'render_errors' => true,
//        ], $options);
//
//        $multiOptions = $this->getMultiOptions();
//
//        if (!array_key_exists($value, $multiOptions)) {
//            throw new Exception("Value '$value' not found in multi options");
//        }
//
//        // ak je string, tak to chapeme ako forced error mesasge
//        $forcedErrorMsg = null;
//        if (is_string($o['render_errors'])) {
//            $forcedErrorMsg = $o['render_errors'];
//        }
//
//        $data = $this->getRenderData('');
//        $label = $o['escape_label'] ? htmlspecialchars($this->getLabel()) : $this->getLabel();
//        $errs = $o['render_errors'] ? $this->renderErrors($forcedErrorMsg) : '';
//        $mmType = !empty($data['type']) ? "mmfe-$data[type]" : "";
//        $class = trim("$data[required_cls] $data[needfix_cls]");
//
//        $attrs = $this->getAttributes();
//        $attrs['name']  = $this->getName();
//
//        $out = '';
//
//        return $out;
//    }

    /**
     * Not only private DRY helper
     *
     * @param $value
     * @param $label
     * @return string
     */
    public function renderSingleOption($value, $label, array $attrs = null)
    {
        $el = $this;
        if ($this->_translate) {
            $label = $this->_translate->translate($label);
        }
        $attrs = array_merge(
            (array) $attrs,
            ['type' => 'radio', 'name' => $el->getName(), 'value' => $value]
        );

        if ($el->getValue() == $value) {
            $attrs['checked'] = 'checked';
        }

        if ("" == $label) {
            //$attrs['value'] = $value;
            $label = $value;
        }
        return Html::renderTag("input", $attrs) . " $label";// . "\n";
    }


    /**
     * @param array $options
     * @return string
     * @throws Exception
     */
    public function render(array $options = [])
    {
        // merge provided options with defaults
        $o = array_merge([
            'escape_label' => true,
            'render_errors' => true,
            // radio special case render option: false or specific value to be rendered
            'render_single_option' => false,
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
        $class = trim("$data[required_cls] $data[needfix_cls]");

        // <div class="radio">
        //   <label>
        //     <input type="radio" name="optionsRadios" id="optionsRadios2" value="option2">
        //         Option two can be something else and selecting it will deselect option one
        //   </label>
        // </div>

        $multiOptions = $this->getMultiOptions();

        if (!empty($o['render_single_option'])
            && !array_key_exists($o['render_single_option'], $multiOptions)
        ) {
            throw new Exception(
                "Value '$o[render_single_option]' not found in multi options"
            );
        }

        $attrs = $this->getAttributes();
        $attrs['name']  = $this->getName();

        $out = '';

        // render "group" only if single renderer is not active
        if (empty($o['render_single_option'])) {
            $out .= "<div class='" . trim("form-group mmfe-radiogroup $class") . "'>";
            if (!empty($label)) {
                $out .= "\n  <p class='radiogroup-legend'>$label</p>\n";
            }
        }
        foreach ($multiOptions as $value => $label) {

            // skip loop if single is not current
            if (!empty($o['render_single_option']) && $value != $o['render_single_option']) {
                continue;
            }

            if (is_array($label)) {
                throw new Exception(
                    "Nested multi options are not supported for radio element"
                );
            }

            $out .= "  <div class='" . trim("radio mmfe-$mmType $class") . "'>"
                  .   "\n    <label class='$class'>"
                  //.     "\n      " . $renderOption($value, $label)
                  .     "\n      " . $this->renderSingleOption($value, $label)
                  .   "\n    </label>"
                  . "\n  </div>\n";
        }

        $out .= ('' == $errs ? "" : "\n  $errs");

        //
        if (empty($o['render_single_option'])) {
            $out .= "</div>";
        }

        return $out;
    }
}
