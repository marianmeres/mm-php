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
class FormTest extends \PHPUnit_Framework_TestCase
{
    public function testBaseFormWorkFlowWorks()
    {
        $f = new Form(array(
            'elements' => array(
                new Element\Text('first'),
                new Element\Text('last', array(
                    'filters' => array(
                        new FilterCallback(function($value){
                            return strtoupper(trim($value));
                        }),
                    ),
                    'required' => 1,
                )),
            ),
            'attributes' => array(
                'action' => '/some',
                'method' => 'post',
            ),
            'translate' => new \MM\Util\Translate(array(
                'translation' => array(
                    'foo' => 'bar'
                )
            )),
        ));

        //
        $this->assertEquals('/some', $f->attr('action'));
        $this->assertEquals('post', $f->attr('method'));

        // vyssi prazdny form nie je validny
        $this->assertFalse($f->isValid());
        $err = $f->getErrors();
        $this->assertEmpty($err['first']);
        $this->assertNotEmpty($err['last']);

        // prve je nepovinne, teda stale nevalidny
        $f->setData(array('first' => 'james'));
        $this->assertFalse($f->isValid());

        // az teraz bude validovat
        $f->setData(array('last' => ' bond '));
        $this->assertTrue($f->isValid());

        // vratene data musi bayt vyfiltrovane
        $this->assertEquals($f->getData(), array(
            'first' => 'james', 'last' => 'BOND'
        ));

// $lastValidators = $f->last->getValidators();
// prx($lastValidators);

        //
        $err = $f->getErrors();
        $this->assertEmpty($err['first']);
        $this->assertEmpty($err['last']);

        $this->assertInstanceOf('MM\Util\Translate', $f->getTranslate());
        $this->assertEquals('bar', $f->getTranslate()->translate('foo'));

        // tu si checkneme ci bol translator korektne nalinkovany az do
        // validatora
        $lastValidators = $f->last->getValidators();
        $this->assertCount(1, $lastValidators);
        $this->assertInstanceOf('MM\Form\Validator\Required', $lastValidators[0]);
        $this->assertInstanceOf(
            'MM\Util\Translate', $lastValidators[0]->getTranslate()
        );
        $this->assertEquals(
            'bar', $lastValidators[0]->getTranslate()->translate('foo')
        );
    }

    public function testHasErrorsWorks()
    {
        $f = new Form(array(
            'elements' => array(
                new Element\Text('a', [
                    'required' => true
                ]),
            ),
        ));

        $valid = $f->isValid();
        $errors = $f->getErrors();
        $hasError = $f->hasError();

        $this->assertFalse($valid);
        $this->assertNotEmpty($errors); // lebo to je podla elementu
        $this->assertTrue($hasError);
    }

    public function testFormElementsAreAccessibleViaGetOverload()
    {
        $f = new Form(array(
            'elements' => array(
                new Element\Text('first'),
            ),
        ));

        $this->assertInstanceOf("\MM\Form\Element\Text", $f->first);
    }

    public function testFormIteratesOverItsElements()
    {
        $f = new Form(array(
            'elements' => array(
                new Element\Text('first'),
                new Element\Text('last'),
            ),
        ));
        foreach ($f as $e) {
            $this->assertInstanceOf("\MM\Form\Element\Text", $e);
        }
    }

    public function testFormValidationPassesItselfAsContextToElementValidation()
    {
        $f = new Form(array(
            'elements' => array(
                new Element\Text('email'),
                new Element\Text('email_check', array(
                    'validators' => array(
                        new ValidatorCallback(function($value, $context){
                            // context je parent form
                            if ($value != $context->email->getValue()) {
                                return "emails don't match";
                            }
                            return true;
                        })
                    ),
                )),
            ),
        ));

        $f->setData(array(
            'email' => 'james@bond.co.uk',
            'email_check' => 'james@bond.com',
        ));
        $this->assertFalse($f->isValid());


        // opravime
        $f->email_check->setValue('james@bond.co.uk');
        $this->assertTrue($f->isValid());
    }

    public function testFormThrowsOnAddingElementsWithExistingName()
    {
        $this->setExpectedException("\MM\Form\Exception");
        $f = new Form(array(
            'elements' => array(
                new Element\Text('first'),
                new Element\Text('first'),
            ),
        ));
    }
}