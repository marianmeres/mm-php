<?php
/**
 * @author Marian Meres
 */
namespace MM\Model;

require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/_files/PersistentModelFoo.php";

use MM\Model\AbstractModel;
use MM\Model\AbstractPersistentModel;

/**
 * @group mm-model
 */
class PersistentModelTest extends \PHPUnit_Framework_TestCase
{
    public function testGetIdInfoWorks()
    {
        $m = new \MM_Model_PersistentModelFoo();
        $m->id = 123;
        $this->assertEquals(123, $m->getId());

        $m2 = new \MM_Model_PersistentModelFoo2();
        $m2->some_id = 2;
        $this->assertEquals(['some_id' => 2, 'user_id' => null], $m2->getId());
    }

    public function testSetIdInfoWorks()
    {
        $m2 = new \MM_Model_PersistentModelFoo2();
        $m2->setId([
            'some_id' => 2, 'user_id' => 3
        ]);

        $this->assertEquals(3, $m2->user_id);

        try {
            $m2->setId('asd');
            $this->fail("Should have failed on invalid argument");
        } catch(\InvalidArgumentException $e){}
    }
    
    public function testSetNullIdWorks()
    {
        //$x = (array) null;
        //prx($x);

        $m = new \MM_Model_PersistentModelFoo();
        $m->id = null;

//        $m = new \MM_Model_PersistentModelFoo([
//            'id' => null
//        ]);
    }
}