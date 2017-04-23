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
class ElementTextTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseElementWorkFlowWorks()
    {
        $e = new Element\Text('meno', array(
            'label' => 'Nomen Omen',
            'attributes' => array(
                'id' => 'kokos',
                'onclick' => 'alert(1)',
            ),
            'filters' => array(
                new FilterCallback(function($value){
                    return trim($value);
                }),
                new FilterCallback(function($value){
                    return strtoupper($value);
                }),
            ),
            'validators' => array(
                new ValidatorCallback(function($value){
                    if ($value != 'SECRET') {
                        return "Sesam will not open for '$value'";
                    }
                    return true;
                })
            ),
        ));

        // prkotiny
        $this->assertEquals("meno", $e->getName());
        $this->assertEquals("Nomen Omen", $e->getLabel());
        $this->assertEquals("kokos", $e->getAttribute('id'));
        $this->assertEquals("alert(1)", $e->getAttribute('onclick'));

        // jadro
        $e->setValue(" aBc ");

        // hodnota musi byt otrimovana a uppercasnuta, lebo filtre
        $this->assertEquals("ABC", $e->getValue());
        $this->assertEquals(" aBc ", $e->getRawValue());

        // ale nesmie validovat
        $this->assertFalse($e->isValid());

        // a musi mat aj korektne error message
        $errors = $e->getErrors();
        $this->assertCount(1, $errors);
        $this->assertEquals("Sesam will not open for 'ABC'", $errors[0]);

        // a naopak, toto musi validovat (filter su efektivne vzdy)
        $e->setValue(" SECret   ");
        $this->assertEquals("SECRET", $e->getValue());
        $this->assertTrue($e->isValid());
        $this->assertEmpty($e->getErrors());
    }

    public function testElementMarkedAsRequiredInternallyUsesRequiredValidator()
    {
        $e = new Element\Text('meno', array(
            'required' => true,
        ));

        $this->assertFalse($e->isValid());
        $v = $e->getValidators();
        $this->assertInstanceOf("\MM\Form\Validator\Required", $v[0]);

        // vsetko bude inak
        $e->setRequired(false);

        // toto je nevyhnutne len tu v testoch - lebo on si pamata error message
        // (sme stale existujuca instancia), v praxi k zmene required na urovni
        // servera najskor nema dovod preco dochadzat
        $e->setErrors(array());

        $this->assertTrue($e->isValid());
    }
}