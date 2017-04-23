<?php
/**
 * @author Marian Meres
 */
namespace MM\Form\Element;

use MM\Form\Element;
use MM\Util\Html;

class Hidden extends Element
{
    /**
     * @var array
     */
    protected $_defaultAttributes = array(
        'type' => 'hidden'
    );

    /**
     * @param array $options
     * @return string
     */
    public function render(array $options = [])
    {
        // no label, no error... makes no sense for hidden
        return $this->renderCoreTag() . "\n";
    }
}
