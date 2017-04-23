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
class RadioTest extends \PHPUnit_Framework_TestCase
{
    public function testTextareaRenderingWorks()
    {
        $e = new Element\Radio('foo', array(
            'label' => 'foo textarea',
            'multiOptions' => [
                'a' => null, 'b' => 'label b', 'c' => 'label c'
            ]
        ));

        //prx($e->renderTag());

        $rendered = $e->render();

        //prx($rendered);

        $this->assertEquals(3, preg_match_all('/type="radio"/', $rendered));
        $this->assertEquals(3, preg_match_all('/name="foo"/', $rendered));

        $this->assertEquals(1, preg_match_all('/value="a"/', $rendered));
        $this->assertEquals(1, preg_match_all('/value="b"/', $rendered));
        $this->assertEquals(1, preg_match_all('/value="c"/', $rendered));
    }
}