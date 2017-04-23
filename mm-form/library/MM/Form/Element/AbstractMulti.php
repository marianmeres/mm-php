<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;
use MM\Form\Validator;

abstract class AbstractMulti extends Element
{
    /**
     * @var array
     */
    protected $_multiOptions = array();

    /**
     * @var bool
     */
    protected $_useInArrayValidator = true;

    /**
     * @param bool $flag
     * @return $this
     */
    public function setUseInArrayValidator($flag = true)
    {
        $this->_useInArrayValidator = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseInArrayValidator()
    {
        return $this->_useInArrayValidator;
    }

    /**
     * @param array $multiOptions
     * @return $this
     */
    public function setMultiOptions(array $multiOptions)
    {
        $this->clearMultiOptions();
        return $this->addMultiOptions($multiOptions);
    }

    /**
     * @return array
     */
    public function getMultiOptions()
    {
        return $this->_multiOptions;
    }

    /**
     * @param array $multiOptions
     * @return $this
     */
    public function addMultiOptions(array $multiOptions)
    {
        foreach ($multiOptions as $option => $value) {
            $this->addMultiOption($option, $value);
        }
        return $this;
    }

    /**
     * @param $option
     * @param null $value
     * @return $this
     */
    public function addMultiOption($option, $value = null)
    {
        // POZNAMKA: value tu moze byt aj pole, potom vsak option bude sluzit
        // ako optgroup label a pole value bude pouzite na inArray validaciu (if any)
        $this->_multiOptions[$option] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function clearMultiOptions()
    {
        $this->_multiOptions = array();
        return $this;
    }

    /**
     * @param null $context
     */
    protected function _validate($context = null)
    {
        if ($this->_useInArrayValidator
            && !$this->hasValidator("\MM\Form\Validator\InArray")) {

            // pripravime data pre InArray validator
            $values = [];
            foreach ($this->getMultiOptions() as $value => $label) {
                if (is_array($label)) { // optgroup
                    $values = array_merge($values, array_keys($label));
                } else {
                    $values[] = $value;
                }
            }

            $this->addValidator(
                new Validator\InArray($values)
            );
        }

        parent::_validate($context);
    }
}