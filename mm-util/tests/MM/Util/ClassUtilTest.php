<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;


require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-util
 */
class ClassUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testGetLastSegmentNameWorks()
    {
        $data = array(
            'a' => 'a',
            'B' => 'B',
            '\c' => 'c',
            '_d' => 'd',
            'Some_Under_Scored' => 'Scored',
            '\Some\Name\Spaced' => 'Spaced',
            'Some\Name\Spaced2' => 'Spaced2',
        );
        foreach ($data as $k => $v) {
            $this->assertEquals(ClassUtil::getLastSegmentName($k), $v);
        }
    }

    public function testClassUsesDeepWorks()
    {
        require_once __DIR__ . "/_files/class-uses-deep.php";

        $expected = array(
            "MM\Util\ClassUtilTest\HeyTrait",
            "MM\Util\ClassUtilTest\HeyTrait2",
            "MM\Util\ClassUtilTest\HeyTrait3",
        );
        $actual = ClassUtil::classUsesDeep("MM\Util\ClassUtilTest\Potomok");

        $this->assertEquals(count($expected), count($actual));

        // kedze poradie najskor neviem garantovat, tak si to loopneme
        // lebo priame porovnanie by nemuselo sediet
        foreach ($expected as $trait) {
            $this->assertTrue(isset($actual[$trait]));
        }
    }

    public function testClassExistsIsAutoloadableCheckWorks()
    {
        $this->assertTrue(ClassUtil::classExists("MM\Util\Dummy\Dummy"));
        $this->assertFalse(ClassUtil::classExists("Whatever\Not\Existing"));

        // toto je pripad, kde fajl existuje spravne ale nazov psr-0 mapuje
        // do legitimneho fajlu
        $this->assertFalse(ClassUtil::classExists("MM\Util\Dummy\Dummy3"));

    }

    public function testClassExistsPhpErrorInClassFilenameIsRethrown()
    {
        $this->setExpectedException('ErrorException');
        ClassUtil::classExists("MM\Util\Dummy\Dummy2");
    }

    public function testClassExistsPhpErrorExceptionInClassIsRethrown()
    {
        $this->setExpectedException('ErrorException');
        ClassUtil::classExists("MM\Util\Dummy\DummyChild");
    }
}