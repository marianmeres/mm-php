<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Validator;

use MM\Form\ValidatorInterface;
use MM\Util\TranslateInterface as Translate;

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Hodnota, ktora bude validovana
     * @var mixed
     */
    protected $_value;

    /**
     * Hlavny invalid message pouzity pri konvencnom chovani (resp. skor jeho
     * kod). Moze by uplne ignorovany a vypluvany v getMessage manualne ak to
     * bude treba
     * @var string
     */
    protected $_message = "__form_invalid_value";

    /**
     * @var boolean
     */
    protected $_breakFlag = true;

    /**
     * @var \MM\Util\Translate|null
     */
    protected $_translate;

    /**
     * Tieto message budu pouzite ak nebude najdeny translator
     * @var array
     */
    protected $_messageTemplates = array(
        '__form_invalid_value' => "Invalid value '{{value}}'",
    );

    /**
     * Za bezny okolnosti validatory formu dostanu translate priamo z formu,
     * cize netreba rucne setovat kazdemu validatoru
     *
     * @param Translate $translate
     * @return $this
     */
    public function setTranslate(Translate $translate = null)
    {
        $this->_translate = $translate;
        return $this;
    }

    /**
     * @return null|\MM\Util\Translate
     */
    public function getTranslate()
    {
        return $this->_translate;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function setMessageTemplate($key, $value)
    {
        $this->_messageTemplates[$key] = $value;
        return $this;
    }

    /**
     * Toto umyselne defaultne pridava. Pride mi to castejsi use case
     *
     * @param array $messages
     * @param bool $reset
     * @return $this
     */
    public function setMessageTemplates(array $messages, $reset = false)
    {
        if ($reset) {
            $this->_messageTemplates = array();
        }

        $this->_messageTemplates = array_merge(
            $this->_messageTemplates, $messages
        );

        return $this;
    }

    /**
     * @return array
     */
    public function getMessageTemplates()
    {
        return $this->_messageTemplates;
    }

    /**
     * @param $msg
     * @return $this
     */
    public function setMessage($msg)
    {
        $this->_message = $msg;
        return $this;
    }

    /**
     * Konvencne chovanie, moze byt potreba extendovat
     *
     * @return mixed
     */
    public function getMessage()
    {
        // ak mame translator a existuje preklad, pouzijeme
        if ($this->_translate //&& isset($this->_translate[$this->_message])) {
            && $this->_translate->hasTranslationFor($this->_message)) {
            $msg = $this->_translate->translate($this->_message);
        }
        // inak skusime fallback na defaulty ak su
        else if (isset($this->_messageTemplates[$this->_message])) {
            $msg = $this->_messageTemplates[$this->_message];
        }
        // inak neprekladam
        else {
            $msg = $this->_message;
        }
        return str_replace(
            '{{value}}', htmlspecialchars($this->_value), $msg
        );
    }

    /**
     * @param bool $flag
     * @return $this
     */
    public function setBreakChainOnFailure($flag = true)
    {
        $this->_breakFlag = (bool) $flag;
        return $this;
    }

    /**
     * @return bool
     */
    public function getBreakChainOnFailure()
    {
        return $this->_breakFlag;
    }

}