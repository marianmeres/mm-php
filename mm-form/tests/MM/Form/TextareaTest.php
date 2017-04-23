<?php
/**
 * @author Marian Meres
 */
namespace MM\Form;

use MM\Form\Filter\Callback as FilterCallback;
use MM\Form\Validator\Callback as ValidatorCallback;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-form
 */
class TextareTest extends \PHPUnit_Framework_TestCase
{
    public function testTextareaRenderingWorks()
    {
        $e = new Element\Textarea('foo', array(
            'label' => 'foo textarea',
            //'value' => ''
        ));

        //prx($e->renderTag());

        $rendered = $e->render();

        //prx($rendered);
    }
}