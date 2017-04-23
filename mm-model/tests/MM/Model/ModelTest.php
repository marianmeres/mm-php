<?php
/**
 * @author Marian Meres
 */
namespace MM\Model;

require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/_files/ModelFoo.php";

use MM\Model\AbstractModel;

/**
 * @group mm-model
 */
class ModelAbstractTest extends \PHPUnit_Framework_TestCase
{
    public function testModelInstanceAndOverloadedAccessorsWork()
    {
        $m = new \MM_Model_ModelFoo();

        $this->assertNull($m->id);
        $this->assertTrue(isset($m->id));
    }

    public function testSettingStateAtContructorWorks()
    {
        $m = new \MM_Model_ModelFoo(array(
            'id' => 123,
        ));
        $this->assertEquals(123, $m->id);
    }

    public function testModelAccessorsWorks()
    {
        $m = new \MM_Model_ModelFoo(array(
            'name' => "Ultra",
        ));
        $this->assertEquals('ultra', $m->id);
        $this->assertEquals('Ultra', $m->name);
    }

    public function testAccessingUndefinedFieldThrowsNotice()
    {
        $m = new \MM_Model_ModelFoo();
        $this->setExpectedException("\PHPUnit_Framework_Error"); // notice
        $m->hoho;
    }

    public function testAccessingUndefinedFieldWorksIfSuchGetterExists()
    {
        $m = new \MM_Model_ModelFoo();
        $this->assertEquals('some', $m->some);
    }

    public function testSettingUndefinedPropertyThrowsModelException()
    {
        $this->setExpectedException("MM\Model\Exception");
        $m = new \MM_Model_ModelFoo();
        $m->hoho = 123;
    }

    public function testSettingUndefinedPropertyCanBeIgnored()
    {
        $m = new \MM_Model_ModelFoo();
        $m->useUndefinedPropertySetHandler(false);
        $m->some = 123;

        // enable again
        $m->useUndefinedPropertySetHandler(true);
        try {
            $m->hoho = 456;
            $this->fail("Should have failed...");
        } catch (\MM\Model\Exception $e) {}
    }

    public function testToArrayIsForcingGetters()
    {
        $m = new \MM_Model_ModelFoo();
        $a = $m->toArray();
        $this->assertTrue(is_array($a));
        $this->assertEquals(4, count($a));

        // isShit je true, ale getter to pretypovava na int
        $this->assertFalse(true === $a['isShit']);
        $this->assertTrue(1 === $a['isShit']);
    }

    public function testToArraySkipDefaultsWork()
    {
        $m = new \MM_Model_ModelFoo(array(
            'id' => 123,
        ));
        $a = $m->toArraySkipDefaults();

        $this->assertEquals(1, count($a));
        $this->assertEquals(123, $a['id']);
    }

    public function testToarrayskipdefaultsWithProvidedDefaultsWorks()
    {
        $data = $defaults = array('id' => 123, 'isShit' => 0);
        $expected = array('name' => null, 'bull' => null);

        $m = new \MM_Model_ModelFoo($data);

        // tu mu posuvame rovnake data na skip, cize vlastne ocakavame array_diff
        $a = $m->toArraySkipDefaults($defaults);
        $this->assertSame($expected, $a);
    }

    public function testToarrayskipdefaultsWithProvidedDefaultsUknownKeysAreIgnored()
    {
        $data     = array('id' => 123, 'isShit' => 0);
        $defaults = array_merge($data, array('too' => 'many', 'of' => 'other ones', 'which' => 'must be ignored'));
        $expected = array('name' => null, 'bull' => null);

        $m = new \MM_Model_ModelFoo($data);

        $a = $m->toArraySkipDefaults($defaults);
        $this->assertSame($expected, $a);
    }

    public function testModelIsNotDirtyAfterSettingValuesAtConstructor()
    {
        $m = new \MM_Model_ModelFoo(array(
            'id' => 1
        ));
        $this->assertFalse($m->isDirty());
        $this->assertEmpty($m->dirtyKeys());
    }

    public function testModelIsDirtyAfterSettingValuesOutsideConstructor()
    {
        $m = new \MM_Model_ModelFoo();
        $this->assertFalse($m->isDirty());
        $m->id = 1;
        $this->assertTrue($m->isDirty());
    }

    public function testModelSettersMustMarkDirtyManually()
    {
        $m = new \MM_Model_ModelFoo();
        $m->name = "foo"; // this has own setter which does not mark dirty
        $this->assertFalse($m->isDirty());
    }

    public function testModelIsNotDirtyAfterSettingSameValue()
    {
        $m = new \MM_Model_ModelFoo(array('id' => 1));
        $this->assertFalse($m->isDirty());
        $m->id = 1;
        $this->assertFalse($m->isDirty());
        $m->id = 2;
        $this->assertTrue($m->isDirty());
    }

    public function testModelShowsCorrectDirtyFields()
    {
        $m = new \MM_Model_ModelFoo();
        $m->id = 1;
        $m->bull = 'shit';
        $this->assertTrue($m->isDirty());
        $this->assertEquals(array('id'=>'id','bull'=>'bull'), $m->dirtyKeys());
    }

    public function testModelCanBeMarkedDirtyOrCleanManually()
    {
        $m = new \MM_Model_ModelFoo();

        // manually one key
        $m->markDirty('id');
        $m->markDirty('bull');
        $this->assertTrue($m->isDirty());
        $this->assertEquals(2, count($m->dirtyKeys()));

        // bulk cleanup
        $m->markClean();
        $this->assertFalse($m->isDirty());
        $this->assertEmpty($m->dirtyKeys());

        // bulk dirt
        $m->markDirty();
        $this->assertTrue($m->isDirty());
        $this->assertEquals(4, count($m->dirtyKeys()));

        // manual cleanup
        $m->markClean(array('id', 'bull'));
        $this->assertTrue($m->isDirty());
        $this->assertEquals(2, count($m->dirtyKeys()));
        $this->assertFalse(in_array('id', $m->dirtyKeys()));
        $this->assertFalse(in_array('bull', $m->dirtyKeys()));
    }

    public function testDirtyWatchingCanBeDisabled()
    {
        $m = new \MM_Model_ModelFoo();
        $m->watchDirty(false);
        $m->id = 1;
        $this->assertFalse($m->isDirty());
    }


    public function testIssetAndEmptyCallsOnMagicFieldsWorks()
    {
        $m = new \MM_Model_ModelFoo();

        // vsetky definovane fieldy
        $this->assertTrue(isset($m->id));
        $this->assertTrue(isset($m->name));
        $this->assertTrue(isset($m->bull));
        $this->assertTrue(isset($m->isShit));

        // akekolvek nedefinovane nesmu byt isset
        $this->assertFalse(isset($m->foo));
        $this->assertFalse(isset($m->bar));

        // "empty" na definovane musi odpovedat realite
        $this->assertTrue(empty($m->id));
        $this->assertTrue(empty($m->name));
        $this->assertTrue(empty($m->bull));
        $this->assertFalse(empty($m->isShit)); // jediny defaultne neempty

        // akolvek nedefinovane musia byt empty
        $this->assertTrue(empty($m->foo));
        $this->assertTrue(empty($m->bar));
    }
}
