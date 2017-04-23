<?php
/**
 * @author Marian Meres
 */
namespace MM\Model;

require_once __DIR__ . "/_bootstrap.php";
require_once __DIR__ . "/_files/ModelFoo.php";

/**
 * @group mm-model
 */
class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectionAcceptsCorrectInstance()
    {
        $c = new Collection('\stdClass');
        $c[] = new \stdClass();
        $c[] = new \stdClass();

        $this->assertEquals(2, count($c));
    }

    public function testCollectionDoesNotAcceptWrongInstance()
    {
        $this->setExpectedException("MM\Model\Exception");
        $c = new Collection('some');
        $c[] = new \stdClass();
    }

    public function testCollectionAcceptsMethodWorks()
    {
        $c = new Collection('\stdClass');
        $this->assertTrue($c->accepts(new \stdClass()));

        $this->assertFalse($c->accepts(new \DateTime()));
    }

    public function testContainsMethodWorks()
    {
        $c = new Collection('\stdClass');

        $f1 = new \stdClass();
        $f1->name = 'f1';

        $f2 = new \stdClass();

        $c[] = $f1;

        $this->assertTrue($c->contains($f1));
        $this->assertFalse($c->contains($f2));

        // "==" operator in action
        $f2->name = 'f1';
        $this->assertTrue($c->contains($f2));
        $this->assertFalse($c->contains($f2, $strict = true));
    }

    public function testUnshiftWorks()
    {
        $c = new Collection('\stdClass');

        $f1 = new \stdClass();
        $f1->name = 'f1';

        $f2 = new \stdClass();
        $f2->name = 'f2';

        $c[] = $f1;
        $this->assertEquals('f1', $c[0]->name);

        $c[] = $f2;
        $this->assertEquals('f1', $c[0]->name);

        $c->unshift($f2);
        $this->assertEquals('f2', $c[0]->name);
        $this->assertEquals('f1', $c[1]->name);
        $this->assertEquals('f2', $c[2]->name);
    }

    public function testAddWorks()
    {
        $c = new Collection('\stdClass');
        $entities = array(
            new \stdClass(), new \stdClass()
        );
        $c->add($entities);
        $this->assertEquals(count($c), count($entities));
    }

    public function testAddAppendsByDefault()
    {
        $c = new Collection('\stdClass');
        $c[] = new \stdClass();
        $entities = array(
            new \stdClass(), new \stdClass()
        );
        $c->add($entities);
        $this->assertEquals(count($c), count($entities) + 1);
    }

    public function testAddCanResetCollection()
    {
        $c = new Collection('\stdClass');
        $c[] = new \stdClass();
        $entities = array(
            new \stdClass(), new \stdClass()
        );
        // add with reset
        $c->add($entities, true);
        $this->assertEquals(count($c), count($entities));
    }

    public function testAddValidatesType()
    {
        $c = new Collection('some');
        $this->setExpectedException("MM\Model\Exception");
        $c->add(array(new \stdClass));
    }

    public function testEmptyCollectionIsNotDirtyUnlessExplicitellyMarkedSo()
    {
        $c = new Collection('\stdClass');
        $this->assertFalse($c->isDirty());

        // explicitne oznacime
        $c->markDirty();
        $this->assertTrue($c->isDirty());

        $c->markClean();
        $this->assertFalse($c->isDirty());

        $c->resetDirtyMark();
        $this->assertFalse($c->isDirty());
    }

    public function testDirtyFlagHasHigherPriotiryOverFallbackToModels()
    {
        $m = new \MM_Model_ModelFoo;
        $c = new Collection('\MM_Model_ModelFoo');

        // pridame model do kolekcie
        $c[] = $m;

        // zatial sme stale cisty
        $this->assertFalse($c->isDirty());

        // neskor model oznacime ako spinavy
        $m->markDirty();

        // a teda kolekcia je spinava lebo interny model je spinavy
        $this->assertTrue($c->isDirty());

        // explicitne ju oznacime ako cistu napriek tomu, ze model je nadalej
        // spinavy
        $c->markClean();
        $this->assertFalse($c->isDirty());
        $this->assertTrue($m->isDirty());

        // vyssie overime aj opacnom garde
        $c->markDirty();
        $m->markClean();
        $this->assertTrue($c->isDirty());
        $this->assertFalse($m->isDirty());
    }


}