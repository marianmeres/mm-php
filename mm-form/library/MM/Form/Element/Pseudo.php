<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;
use MM\Form\Exception;

/**
 * Toto je pseudo element...
 */
class Pseudo extends Element
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'pseudo'
    );

    /**
     * @return mixed|string
     */
    public function renderCoreTag()
    {
        return $this->getValue();
    }

    /**
     * @param $flag
     * @throws Exception
     * @return void
     */
    public function setRequired($flag)
    {
        if ($flag) {
            throw new Exception(
                "Pseudo element cannot be marked as required"
            );
        }
    }

    /**
     * @param null $context
     */
    protected function _validate($context = null)
    {
        // void
    }
}