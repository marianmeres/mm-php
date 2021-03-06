<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;


require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-util
 */
class TranslateTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultLangIsEN()
    {
        $t = new Translate;
        $this->assertEquals('EN', $t->getLang());
    }

    public function testTranslateWorks()
    {
        $t = new Translate(array(
            'lang' => 'de', // internally uppercase
            'translation' => array(
                'en' => array(
                    '1' => 'one', '2' => 'two', '3' => 'Hello XXX'
                ),
                'de' => array(
                    '1' => 'ein', '2' => 'zwei'
                ),

                // jednorozmerne pole bude pridane akutalnemu jazyku
                // nastavenemu cez 'lang'
                '4' => 'scheise',
            ),
        ));

        // prx($t->getData());

        $this->assertEquals('DE', $t->getLang());

        $this->assertEquals('ein', $t->translate('1'));
        $this->assertEquals('ein', $t('1')); // __invoke
        $this->assertEquals('zwei', $t['2']); // offsetGet
        $this->assertEquals('3', $t['3']); // offsetGet
        $this->assertEquals('scheise', $t['4']);

        $t->setLang('EN');

        $this->assertEquals('one', $t('1')); // __invoke
        $this->assertEquals('two', $t['2']); // offsetGet
        $this->assertEquals('Hello World', $t('3', 'World'));
        $this->assertEquals('4', $t['4']); // offsetGet

        // remove kluca
        $t->addTranslation(array('3' => null));
        $this->assertEquals('3', $t('3', 'World'));
    }
}