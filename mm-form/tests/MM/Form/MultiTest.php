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
class MultiTest extends \PHPUnit_Framework_TestCase
{
    public function testMultiBasicFlowWorks()
    {
        $e = new Element\Select('meno', array(
            //'required' => true,
        ));

        $options = array(
            "some" => null,
            "another" => 123,
            "third" => array(1=>1, 2, 3)
        );

        $e->setMultiOptions($options);
        $this->assertEquals($options, $e->getMultiOptions());

        // este nema hodnotu, ale true lebo nie je povinny
        $this->assertTrue($e->isValid());

        // false lebo uz je povinny
        $e->setRequired(true);
        $this->assertFalse($e->isValid());
        //prx($e->getErrors());

        // teraz setneme znamu vec a musi byt validne
        $e->setValue("some");
        $this->assertTrue($e->isValid());

        // naopak neznama vec nesmie byt valid
        // NOTE: kluc 'third' je sice vyssie definovany, ale tym, ze hodnota
        // je pole, stava sa z neho optgroup label a nie value
        $e->setValue("third");
        $this->assertFalse($e->isValid());
        //prx($e->getErrors());

        // teraz skusime dat vnorenu hodnotu (think optgroup)
        $e->setValue(3);
        $this->assertTrue($e->isValid());
    }
}