<?php
/**
 * Silne inspirovane ZF, drasticky zjednodusene
 *
 * @author Marian Meres
 */
namespace MM\Form;

use MM\Form\FilterInterface as Filter;
use MM\Form\ValidatorInterface as Validator;

use MM\Util\ClassUtil;
use MM\Util\Html;
use MM\Util\TranslateInterface as Translate;

/**
 * Element abstrahuje input field, t.j. ma meno, hodnotu, atributy...
 *
 * Navyse filte a validatori. Filtre sa aplikuju na hodnotu okamzite,
 * validatori az po zavolani isValid (a vzdy len raz pre aktualnu value).
 * Validatori setuju error message.
 */
class Element
{
    /**
     * @var string
     */
    protected $_name;

    /**
     * @var string
     */
    protected $_label;

    /**
     * @var string
     */
    // protected $_type;

    /**
     * @var mixed
     */
    protected $_value;

    /**
     * @var mixed
     */
    protected $_rawValue;

    /**
     * @var array
     */
    protected $_attributes = array();

    /**
     * @var array
     */
    protected $_defaultAttributes = array();

    /**
     * @var array
     */
    protected $_filters = array();

    /**
     * @var array
     */
    protected $_validators = array();

    /**
     * @var array
     */
    protected $_errors = array();

    /**
     * Je element povinny? (Musi byt vyplneny neempty stringom)
     * @var boolean
     */
    protected $_required = false;

    /**
     * Full quialified name required validatora
     * @var string
     */
    protected $_requiredValidatorClassName = "\MM\Form\Validator\Required";

    /**
     * Interny flag, indikujuci ci uz bola validacia vykonana.
     * Pozor, nepliest si "isValid" versus "isValidated"
     * @var boolean
     */
    protected $_isValidated = false;

    /**
     * Defaultne app-wide form element templaty (markup podla vzoru
     * twitter bootstrap-u). Nic nebrani prepisat a definovat vlastne, pripadne
     * renderovat uplne na inej urovni (rucne vo views)...
     * @var array
     */
    protected static $_globalTemplates;

    /**
     * Instancne templaty, vyssia priorita nad globalnymi
     * @var array
     */
    protected $_templates = array();

    /**
     * @var \MM\Util\Translate
     */
    protected $_translate;

    /**
     * @param $name
     * @param array $options
     */
    public function __construct($name, $options = array())
    {
        $options['name'] = $name;
        ClassUtil::setOptions($this, $options);
    }

    /**
     * @param Translate $translate
     * @return $this
     */
    public function setTranslate(Translate $translate = null)
    {
        $this->_translate = $translate;

        // unset nie je delgovany dalej
        if ($this->_translate) {
            foreach ($this->_validators as $validator) {
                /** @var ValidatorInterface $validator */
                // setneme iba tym ktory nemaju
                if (!$validator->getTranslate()) {
                    $validator->setTranslate($this->_translate);
                }
            }
        }

        return $this;
    }

    /**
     * @return \MM\Util\Translate
     */
    public function getTranslate()
    {
        return $this->_translate;
    }

    /**
     * @param $name
     * @return $this
     * @throws Exception
     */
    public function setName($name)
    {
        $name = trim($name);
        if ("" == $name) {
            throw new Exception("Name must not be empty");
        }
        $this->_name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     *
     */
    // public function setType($type)
    // {
    //     $this->_type = $type;
    //     return $this;
    // }


    // public function getType()
    // {
    //     return $this->_type;
    // }

    /**
     * @param $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->_label = $label;
        return $this;
    }

    /**
     * @return mixed|string
     */
    public function getLabel()
    {
        if ($this->_translate) {
            return $this->_translate->translate($this->_label);
        }
        return $this->_label;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->_isValidated = false;
        $this->_errors      = array();
        $this->_rawValue    = $value;
        $this->_value       = $this->_filter($value);
        return $this;
    }

    /**
     * Vrati vyfiltrovanu value
     * @return mixed
     */
    public function getValue()
    {
        return $this->_value;
    }

    /**
     * Vrati surovu value
     * @return mixed
     */
    public function getRawValue()
    {
        return $this->_rawValue;
    }

    /**
     * @param array $attributes
     * @param bool $reset
     * @return $this
     */
    public function setAttributes(array $attributes, $reset = true)
    {
        if ($reset) {
            $this->_attributes = array();
        }
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    /**
     * @param $name
     * @param $value
     * @return $this
     */
    public function setAttribute($name, $value)
    {
        $this->_attributes[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @return null|string
     */
    public function getAttribute($name)
    {
        $attrs = $this->getAttributes();
        return isset($attrs[$name]) ? $attrs[$name] : null;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $out = array_merge($this->_defaultAttributes, $this->_attributes);

        // if no id provided, generate one
        if (empty($out['id'])) {
            $out['id'] = $this->_createId();
        }

        return $out;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setAttribute('id', $id);
    }

    /**
     * @return null|string
     */
    public function getId()
    {
        return $this->getAttribute('id');
    }

    /**
     * Internal to be extended if needed
     * @return string
     */
    protected function _createId()
    {
        return sprintf("mmfe_%s",
            preg_replace("/\W/", "_", $this->getName())
        );
    }

    /**
     * @param array $filters
     * @param bool $reset
     * @return $this
     */
    public function setFilters(array $filters, $reset = true)
    {
        if ($reset) {
            $this->_filters = array();
        }
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }
        return $this;
    }

    /**
     * @param FilterInterface $filter
     * @return $this
     */
    public function addFilter(Filter $filter)
    {
        $this->_filters[] = $filter;
        return $this;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * @param array $validators
     * @param bool $reset
     * @return $this
     */
    public function setValidators(array $validators, $reset = true)
    {
        if ($reset) {
            $this->_validators = array();
        }
        foreach ($validators as $validator) {
            $this->addValidator($validator);
        }
        return $this;
    }

    /**
     * @param $validatorOrClassName
     * @param bool $strictComparison
     * @return bool
     */
    public function hasValidator($validatorOrClassName, $strictComparison = false)
    {
        foreach ($this->_validators as $validator) {
            if ($validator instanceof $validatorOrClassName) {
                if (is_object($validatorOrClassName) && $strictComparison) {
                    return $validator === $validatorOrClassName;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param ValidatorInterface $validator
     * @param bool $prepend
     * @return $this
     */
    public function addValidator(Validator $validator, $prepend = false)
    {
        // return early ak uz mame
        if ($this->hasValidator($validator, $strict = false)) {
            return $this;
        }

        // ak mame translate tu, ale nie vo validatore tak posuvame referenciu
        if ($this->_translate && !$validator->getTranslate()) {
            $validator->setTranslate($this->_translate);
        }

        if ($prepend) {
            array_unshift($this->_validators, $validator);
        } else {
            array_push($this->_validators, $validator);
        }
        return $this;
    }

    /**
     * @param $validatorOrClassName
     * @return $this
     */
    public function removeValidator($validatorOrClassName)
    {
        foreach ($this->_validators as $idx => $validator) {
            if ($validator instanceof $validatorOrClassName) {
                unset($this->_validators[$idx]);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getValidators()
    {
        return $this->_validators;
    }

    /**
     * @param null $context
     * @return bool
     */
    public function isValid($context = null)
    {
        $this->_validate($context);
        return empty($this->_errors);
    }

    /**
     * @param array $messages
     * @param bool $reset
     * @return $this
     */
    public function setErrors(array $messages, $reset = true)
    {
        if ($reset) {
            $this->_errors = array();
        }
        foreach ($messages as $msg) {
            $this->addError($msg);
        }
        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    public function addError($message)
    {
        $this->_errors[] = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param $flag
     * @return $this
     */
    public function setRequired($flag)
    {
        $flag = (bool) $flag;

        if ($flag != $this->_required) {
            $this->_required = $flag;

            // ak dochadza k zmene, tak aj interny flag resetujem, lebo
            // v akomkolvek stave sme teraz, znovu validovat bude nutne
            $this->_isValidated = false;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return (bool) $this->_required;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function _filter($value)
    {
        /** @var FilterInterface $filter */
        foreach ($this->_filters as $filter) {
            $value = $filter->filter($value);
        }
        return $value;
    }

    /**
     * @param null $context
     */
    protected function _validate($context = null)
    {
        // validovat chceme vzdy len raz
        if ($this->_isValidated) {
            return;
        }

        // validate just once per value
        $this->_isValidated = true;

        // FEATURE: ak nie je povinny a je empty, tak skipujeme celu
        // validaciu (analogia k "allowEmpty" pri ZF1)
        if (!$this->_required && "" == $this->_value) {
            return;
        }

        // ak je povinne, tak manualne vlozime required validator ako prvy...
        // inak ho vyhodime ak nahodou existuje
        $rv = $this->_requiredValidatorClassName;
        if ($this->_required && !$this->hasValidator($rv)) {
            $this->addValidator(new $rv, $prepend = true);
        } else if (!$this->_required) {
            $this->removeValidator($rv);
        }

        foreach ($this->_validators as $validator) {
            /** @var ValidatorInterface $validator */
            if (!$validator->isValid($this->_value, $context)) {
                $this->_errors[] = $validator->getMessage();
                if ($validator->getBreakChainOnFailure()) {
                    break;
                }
            }
        }
    }


/*******************************************************************************
 * POZNAMKA K NIZSIEMU RENDERINGU
 * nekladie si za ciel vyriesit vsetky use-casy, ide vylucne o rychly prototyping.
 * Kludne renderovat akokolvek inak...
*******************************************************************************/

    /**
     * DRY
     * @param string $custom
     * @return array
     */
    public function getRenderData($custom = '')
    {
        return [
            'id'           => $this->getAttribute('id'),
            'type'         => $this->getAttribute('type'),
            'required_cls' => $this->_required ? 'required' : '',
            'needfix_cls'  => !empty($this->_errors) ? 'error' : '',
            'custom'       => $custom,
            'label'        => $this->getLabel(),
        ];
    }

    /**
     * Renders most common use case. To be extended.
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {
        // merge provided options with defaults
        $o = array_merge([
            'escape_label' => true,
            'render_errors' => true,
            'custom_html' => '',
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

        // bootstrap 3
        // <div class="form-group">
        //     <label for="exampleInputEmail1">Email address</label>
        //     <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Email">
        // </div>
        $errCls = count($this->getErrors()) ? "has-danger" : '';

        $out = "<div class='" . trim("form-group $class $data[id] $errCls") . "'>"
             .   ('' == $label ? "" : "\n  <label for='$data[id]' class='$class'>$label</label>")
             .   "\n  " . $this->renderCoreTag()
             .   ('' == $o['custom_html'] ? "" : "<p class='help-block'>$o[custom_html]</p>")
             .   ('' == $errs ? "" : "\n  $errs")
             . "\n</div>\n";

        return $out;
    }

    /**
     * Renders most common use case. To be extended.
     * @return string
     */
    public function renderCoreTag(array $customAttrs = [])
    {
        // <input type="email" class="form-control" id="exampleInputEmail1" placeholder="Email">

        $attrs = $this->getAttributes();
        $attrs['value'] = $this->getValue();
        $attrs['name']  = $this->getName();

        // class name povolujeme pridat custom
        if (!isset($attrs['class'])) {
            $attrs['class'] = "";
        }
        $attrs['class'] = trim("form-control $attrs[class]");
        if (count($this->getErrors())) {
            $attrs['class'] .= " error form-control-danger";
        }

        // required?
        if ($this->isRequired()) {
            $attrs['required'] = 'required';
        }

        // placeholder prekladama ak je cim
        if (!empty($attrs['placeholder']) && $this->_translate) {
            $attrs['placeholder'] = $this->_translate->translate(
                $attrs['placeholder']
            );
        }

        $attrs = array_merge($attrs, $customAttrs);

        return Html::renderTag('input', $attrs);
    }

    /**
     * Renders most common use case. To be extended.
     * @param null $forcedErrorMsg
     * @return string
     */
    public function renderErrors($forcedErrorMsg = null)
    {
        if (empty($this->_errors)) {
            return '';
        }

        $e = $this->_errors;
        if ($forcedErrorMsg) {
            $e = array($forcedErrorMsg);
        }

        $out = '<ul class="mmfe-errors">';
        foreach ($e as $msg) {
            $out .= sprintf("<li>%s</li>", htmlspecialchars($msg));
        }
        $out .= '</ul>';

        return $out;
    }

}
