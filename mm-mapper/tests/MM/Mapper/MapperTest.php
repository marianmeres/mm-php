<?php
namespace MM\Mapper\Test\Dao;

use MM\Mapper\Dao\DbTable;
use MM\Mapper\Exception;
use MM\Mapper\Mapper;
use MM\Mapper\Test\Foo2Model;
use MM\Mapper\Test\FooModel;
use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;

require_once __DIR__ . "/_bootstrap.php";

require_once __DIR__ . "/_files/FooModel.php";
require_once __DIR__ . "/_files/Foo2Model.php";

/**
 * @group mm-mapper
 */
class MapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbUtilPdo
     */
    public $db;

    /**
     * @var DbTable
     */
    public $fooDao;

    /**
     * @var DbTable
     */
    public $foo2Dao;

    /**
     * @var Mapper
     */
    public $mapper;

    /**
     * @var Mapper
     */
    public $mapper2;

    /**
     * @param string $vendor
     * @return mixed
     */
    public function getSql($vendor = 'sqlite')
    {
        $sql = "
            drop table if exists foo;
            create table foo (
               id   {serial-primary-key},
               name  varchar(255) null
            );

            insert into foo (name) values ('john');
            insert into foo (name) values ('paul');
            insert into foo (name) values ('george');

            drop table if exists foo2;
            create table foo2 (
               id1   int,
               id2   int,
               name  varchar(255) null,
               primary key (id1, id2)
            );
            insert into foo2 (id1, id2, name) values (1, 2, 'a');
        ";
        return SqlHelper::getVendorSql($sql, $vendor);
    }

    public function setUp()
    {
        // $this->dbu = new DbUtilPdo(new \PDO("sqlite::memory:"));
        if (!defined("MM_MAPPER_PDO_JSON_CONFIG")) {
            die("MM_MAPPER_PDO_JSON_CONFIG not defined");
        }

        $this->db = new DbUtilPdo(
            json_decode(MM_MAPPER_PDO_JSON_CONFIG, true)
        );

        $sql = $this->getSql(MM_MAPPER_DB_VENDOR);
        $this->db->getResource()->exec($sql);

        $this->fooDao = new DbTable([
            'table_name' => 'foo', 'db' => $this->db, 'auto_increment' => true,
            'id_info' => 'id'
        ]);

        $this->foo2Dao = new DbTable([
            'table_name' => 'foo2', 'db' => $this->db, 'auto_increment' => false,
            'id_info' => ['id1', 'id2']
        ]);

        Mapper::resetToOutOfTheBoxState();

        $this->mapper = new Mapper([
            'dao' => $this->fooDao,
            'model_fqn' => '\MM\Mapper\Test\FooModel'
        ]);

        $this->mapper2 = new Mapper([
            'dao' => $this->foo2Dao,
            'model_fqn' => '\MM\Mapper\Test\Foo2Model'
        ]);
    }

    public function testSinglePkBasicSaveWorks()
    {
        $mapper = $this->mapper;

        $model = new FooModel([
            'name' => 'james bond'
        ], true);

        // insert
        $mapper->save($model);

        $this->assertFalse($model->isDirty());

        // auto increment musel byt setnuty aj modelu
        $this->assertEquals(4, $model->getId());

        // a koretkne vsetko ulozene
        $this->assertEquals(
            'james bond', $this->db->fetchOne(
                'name', 'foo', ['id' => $model->getId()]
            )
        );

        // update
        $model->name = '007';
        $mapper->save($model);

        $this->assertEquals('007', $model->name);
        $this->assertEquals('007', $this->db->fetchOne('name', 'foo', 'id=4'));
    }

    public function testSinglePkManualIdSaveWorks()
    {
        $mapper = $this->mapper;

        // teraz ulozime autoincrement field so setnutou hodnotou
        $model = new FooModel([
            'id' => 33, 'name' => 'pippen'
        ], true);

        $model->__setIsNew(true);

        // insert
        $mapper->save($model);

        $this->assertEquals('pippen', $this->db->fetchOne('name', 'foo', 'id=33'));

        // update
        $model->name = 'scottie';
        $mapper->save($model);

        $this->assertEquals('scottie', $this->db->fetchOne('name', 'foo', 'id=33'));

        // toto je test pre postgres: dalsi novy musi byt 34 (dao alterlo sequenciu)
        $mapper->save(new FooModel(['name' => 'mj'], true));
        $this->assertEquals('mj', $this->db->fetchOne('name', 'foo', 'id=34'));
    }

    public function testCompositePkSaveWorks()
    {
        $mapper = $this->mapper2;

        $model = new Foo2Model([
            'id1' => 3, 'id2' => 4, 'name' => 'b'
        ], true);
        //prx($model->__getIdInfo());

        $mapper->save($model);

        $this->assertEquals('b', $this->db->fetchOne('name', 'foo2', 'id1=3'));
    }

    public function testFindWorks()
    {
        $mapper = $this->mapper;

        $model = $mapper->find(1);
        $this->assertTrue($model instanceof FooModel);
        $this->assertEquals('john', $model->name);

        $this->assertNull($mapper->find(123, false));

        try {
            $mapper->find(['wrong' => 1]);
            $this->fail("Should have failed on id description mismatch");
        } catch(\MM\Mapper\Dao\Exception $e){}

        // foo2

        $mapper2 = $this->mapper2;

        $model = $mapper2->find(['id1' => 1, 'id2' => 2]);
        $this->assertTrue($model instanceof Foo2Model);
        $this->assertEquals('a', $model->name);

    }

    public function testFindWithNullParameterReturnsNull()
    {
        $mapper = $this->mapper;

        $this->assertNull($mapper->find(null));
    }

    public function testFindAssertsByDefault()
    {
        $mapper = $this->mapper;

        // defaultne hadze
        try {
            $mapper->find(123);
            $this->fail("Should have failed on model not found");
        } catch(\MM\Mapper\Exception $e){}

        // vieme zrelaxovat
        $mapper->find(123, false);
    }

    public function testDeleteWorks()
    {
        $mapper = $this->mapper;

        $mapper->delete(2);

        $this->assertEquals(2, $this->db->fetchCount('foo'));
        $this->assertNull($this->db->fetchRow('*', 'foo', 'id=2'));

        // neexistujuce id nesmie mat ziaden efekt
        $mapper->delete(123);
        $this->assertEquals(2, $this->db->fetchCount('foo'));

        // rovnako skusime aj foo2
        $mapper = $this->mapper2;

        $mapper->delete(['id1' => 234, 'id2' => 345]);
        $mapper->delete(['id1' => 1, 'id2' => 2]);
        $this->assertEquals(0, $this->db->fetchCount('foo2'));

    }

    public function testIdentityMapWorks()
    {
        $mapper = $this->mapper;

        $im = $mapper::dumpIdentityMap(true);
        $this->assertEmpty($im);

        // find
        $model = $mapper->find(1);
        $im = $mapper::dumpIdentityMap(true);
        $this->assertNotEmpty($im['foo'][1]);

        // update
        $model->name = 'lennon';
        $mapper->save($model);
        $this->assertEquals(
            'lennon', $mapper::dumpIdentityMap(true)['foo'][1]['name']
        );
        $this->assertEquals(
            'lennon', $mapper->find(1)->name
        );

        // delete
        $mapper->delete($model);
        $this->assertEmpty($mapper::dumpIdentityMap(true)['foo']);

        // find2
        $mapper->find(2); // toto ulozim 2 do im

        // hard data delete
        $this->db->delete('foo', '1=1');

        // 3 uz nesmie najst
        $this->assertNull($mapper->find(3, false));

        // ale 2 je v im, takze to musi najst normalne
        $model = $mapper->find(2);

        $this->assertEquals(
            'paul', $mapper::dumpIdentityMap(true)['foo'][2]['name']
        );

        // im flush
        $mapper::flushIdentityMap('foo');

        // a teraz uz nenajde ani 2
        $this->assertNull($mapper->find(2, false));
    }

    public function testIdentityMapOnCompositePkWorks()
    {
        $id = ['id1' => 1, 'id2' => 2];
        $id2 = ['id1' => 3, 'id2' => 4];

        // prepare
        $this->db->insert('foo2', array_merge($id2, ['name' => 'x']));

        $mapper = $this->mapper2;

        $normalizedId = $mapper->getNormalizedIdentityMapId($id);
        $normalizedId2 = $mapper->getNormalizedIdentityMapId($id2);

        $im = $mapper::dumpIdentityMap(true);
        $this->assertEmpty($im);


        // find
        $model = $mapper->find($id);
        $im = $mapper::dumpIdentityMap(true);
        $this->assertNotEmpty($im['foo2'][$normalizedId]);

        // update
        $model->name = 'b';
        $mapper->save($model);
        $this->assertEquals(
            'b', $mapper::dumpIdentityMap(true)['foo2'][$normalizedId]['name']
        );
        $this->assertEquals(
            'b', $mapper->find($id)->name
        );

        // delete
        $mapper->delete($model);
        $this->assertEmpty($mapper::dumpIdentityMap(true)['foo2']);

        // find2
        $mapper->find($id2);

        // hard data delete
        $this->db->delete('foo2', '1=1');

        // 1 uz nesmie najst
        $this->assertNull($mapper->find($id, false));

        // ale 2 je v im, takze to musi najst normalne
        $model = $mapper->find($id2);

        $this->assertEquals(
            'x', $mapper::dumpIdentityMap(true)['foo2'][$normalizedId2]['name']
        );

        // im flush
        $mapper::flushIdentityMap('foo2');

        // a teraz uz nenajde ani 2
        $this->assertNull($mapper->find($id2, false));
    }


    public function testFetchAll()
    {
        $mapper = $this->mapper;

        $all = $mapper->fetchAll([]);

        $this->assertCount(3, $all);
    }

}