<?php
/**
 * Silne inspirovane ZF, drasticky zjednodusene
 *
 * @author Marian Meres
 */
namespace MM\Form;

use MM\Form\Element;
use MM\Util\ClassUtil;
use MM\Util\Html;
use MM\Util\TranslateInterface as Translate;

/**
 *
 */
class Form implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected $_elements = array();

    /**
     * @var array
     */
    protected $_attributes = array();

    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'action' => '',
        'method' => 'post',
        // toto by mohlo sposobit problemy, ak by html charset bol iny,
        // ale nasa konvencia je vsade utf-8, takze to bude ok a ide skor o
        // kozmeticku vec, urcite nie spolah na serveri
        'accept-charset' => "utf-8",
    );

    /**
     * @var \MM\Util\Translate
     */
    protected $_translate;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        ClassUtil::setOptions($this, $options);
        $this->_init();
    }

    /**
     *
     */
    protected function _init()
    {
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
            foreach ($this->_elements as $element) {
                /** @var Element $element */
                // setneme iba tym ktory nemaju
                if (!$element->getTranslate()) {
                    $element->setTranslate($this->_translate);
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
     * @param array $elements
     * @param bool $reset
     * @return $this
     */
    public function setElements(array $elements, $reset = true)
    {
        if ($reset) {
            $this->_elements = array();
        }
        foreach ($elements as $element) {
            /** @var Element $element */
            $this->add($element);
        }
        return $this;
    }

    /**
     * @param Element $element
     * @return $this
     * @throws Exception
     */
    public function add(Element $element)
    {
        $name = $element->getName();
        if (isset($this->_elements[$name])) {
            throw new Exception("Element '$name' already added");
        }

        // ak mame tu translator ale v elemente nie, tak ho posuvame referenciu
        if ($this->_translate && !$element->getTranslate()) {
            $element->setTranslate($this->_translate);
        }

        $this->_elements[$name] = $element;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function remove($name)
    {
        unset($this->_elements[$name]);
        return $this;
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->_elements[$name]);
    }

    /**
     * @param $name
     * @return Element
     * @throws Exception
     */
    public function __get($name)
    {
        if (isset($this->_elements[$name])) {
            return $this->_elements[$name];
        }
        throw new Exception("Element '$name' not found");
    }

    /**
     * @param null $context
     * @return bool
     */
    public function isValid($context = null)
    {
        $isValid = true;

        // validatory akceputuju aj volitelny context, co zvacsa znamena, ze
        // posleme seba ako drzitela kolekcie vsetkych elementov
        if (!$context) {
            $context = $this;
        }

        foreach ($this->_elements as $element) { // umyselne vsetky elementy
            /** @var Element $element */
            if (!$element->isValid($context)) {
                $isValid = false;
            }
        }
        return $isValid;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        foreach ($data as $name => $value) {
            if (isset($this->_elements[$name])) {
                /** @var Element $element */
                $element = $this->_elements[$name];
                $element->setValue($value);
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getData()
    {
        $out = array();
        foreach ($this->_elements as $element) {
            /** @var Element $element */
            $out[$element->getName()] = $element->getValue();
        }
        return $out;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        $out = array();
        foreach ($this->_elements as $element) {
            /** @var Element $element */
            $out[$element->getName()] = $element->getErrors();
        }
        return $out;
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        foreach ($this->_elements as $element) {
            /** @var Element $element */
            if ($element->getErrors()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_elements);
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
        return array_merge($this->_defaultAttributes, $this->_attributes);
    }

    /**
     * Shortcut alias
     *
     * @param $name
     * @return null|string
     */
    public function attr($name)
    {
        return $this->getAttribute($name);
    }

    /**
     * Toto robi iba uplne bazalne renderovanie... nie je urcene na realny
     * frontend, ale iba na vyslovene prototypovanie...
     *
     * @param bool $renderElementErrors
     * @return string
     */
    public function render($renderElementErrors = true)
    {
        $out  = $this->renderOpen();
        $out .= $this->renderElements($renderElementErrors);
        $out .= $this->renderClose();
        return $out;
    }

    /**
     * Sugar.
     * @return string
     */
    public function renderOpen()
    {
        return "\n"
             . Html::renderTag('form', $this->getAttributes(), null, false)
             . "<div class='form-in'>\n";
    }

    /**
     * Sugar.
     * @param bool $renderElementErrors
     * @return string
     */
    public function renderElements($renderElementErrors = true)
    {
        $out = '';
        foreach ($this->_elements as $name  => $e) {
            /** @var Element $e */
            $out .= $e->render([
                'render_errors' => $renderElementErrors
            ]);
        }
        return $out;
    }

    /**
     * Sugar.
     * @return string
     */
    public function renderClose()
    {
        return "\n</div></form>\n";
    }

}

