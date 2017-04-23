<?php
namespace MM\Mapper\Test\Dao;

use MM\Mapper\Dao\DbTable;
use MM\Mapper\Exception;
use MM\Mapper\Mapper;
use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;

require_once __DIR__ . "/../_bootstrap.php";

/**
 * @group mm-mapper
 */
class DbTableTest extends \PHPUnit_Framework_TestCase
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

        Mapper::resetToOutOfTheBoxState();

        $this->fooDao = new DbTable([
            'table_name' => 'foo', 'db' => $this->db, 'auto_increment' => true,
            'id_info' => 'id'
        ]);

        $this->foo2Dao = new DbTable([
            'table_name' => 'foo2', 'db' => $this->db, 'auto_increment' => false,
            'id_info' => ['id1', 'id2']
        ]);
    }

    public function testExistsOnSinglePkWorks()
    {
        $dao = $this->fooDao;

        $this->assertTrue($dao->exists(['id' => 3]));
        $this->assertFalse($dao->exists(['id' => 4]));
    }

    public function testExistsOnCompositePkWorks()
    {
        $dao = $this->foo2Dao;

        $this->assertTrue($dao->exists(['id1' => 1, 'id2' => 2]));
        $this->assertFalse($dao->exists(['id1' => 1, 'id2' => 3]));
    }

    public function testCreateOnSinglePkWorks()
    {
        $dao = $this->fooDao;

        // umyselne natvrdo 5
        $dao->create(['id' => 5, 'name' => 'ringo']);
        $this->assertEquals('ringo', $this->db->fetchOne('name', 'foo', 'id=5'));

        // update: po novom toto uz riesi dao same
//        if ($this->db->isPgsql()) {
//            $this->db->execute('alter sequence foo_id_seq restart with 6');
//        }

        // teraz skusime auto increment (id neposielame)
        $lastId = $dao->create(['name' => 'jackson']);
        $this->assertEquals(6, $lastId);
    }

    public function testCreateOnCompositePkWorks()
    {
        $dao = $this->foo2Dao;

        $dao->create(['id1' => 3, 'id2' => 4, 'name' => 'b']);

        $this->assertEquals('b', $this->db->fetchOne('name', 'foo2', 'id1=3'));
    }

    public function testReadWorks()
    {
        $dao = $this->fooDao;
        $row = $dao->read(['id' => 2]);

        $this->assertEquals('paul', $row['name']);
    }

    public function testUpdateWorks()
    {
        $dao = $this->fooDao;

        $updated = $dao->update(['id' => 2], ['name' => 'paulis']);
        $this->assertEquals(1, $updated);

        $this->assertEquals('paulis', $this->db->fetchOne('name', 'foo', 'id=2'));

        $this->assertEquals(
            0, $dao->update(['id' => 35], ['name' => 'paulis'])
        );
    }

    public function testDeleteWorks()
    {
        $dao = $this->fooDao;

        $dao->delete(['id' => 2]);

        $this->assertEquals(2, $this->db->fetchCount('foo'));
        $this->assertNull($this->db->fetchRow('*', 'foo', 'id=2'));

        // ostatne musia zostat
        $this->assertEquals(2, $this->db->fetchCount('foo'));
    }

    public function testEmptyIdDataThrows()
    {
        $dao = $this->fooDao;
        try {
            $dao->delete([]);
            $this->fail("Should have thrown on empty data");
        } catch (Exception $e) {}

        $dao = $this->foo2Dao;
        try {
            $dao->read(['id1' => 1, 'id2' => null]);
            $this->fail("Should have thrown on empty key");
        } catch (Exception $e) {}

        $dao = $this->fooDao;
        try {
            $dao->read(['id' => '']);
            $this->fail("Should have thrown on empty key");
        } catch (Exception $e) {}
    }


    public function testAutoIncrementOnCompositPkThrows()
    {
        $dao = new DbTable([
            'table_name' => 'foo2', 'db' => $this->db,
            'auto_increment' => true,
            'id_info' => ['id1', 'id2']
        ]);

        $this->setExpectedException("\MM\Mapper\Exception");
        $dao->delete(['id1' => 123]);
    }

    public function testInvalidIdDataThrows()
    {
        $dao = $this->fooDao;
        $this->setExpectedException("\MM\Mapper\Exception");
        $dao->delete(['xyz' => 123]);
    }


}