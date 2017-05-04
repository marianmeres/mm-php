<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 03/09/15
 * Time: 09:34
 */

namespace MM\SimpleCache;

require_once __DIR__ . "/_bootstrap.php";

use MM\Util\DbUtilPdo;
use MM\Util\SqlHelper;

/**
 * @group mm-simpleCache
 */
class DbCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DbUtilPdo
     */
    public $dbu;

    /**
     * @var DbCache
     */
    public $cache;

    public function getTestTableSql($vendor = 'sqlite')
    {
        $sql = DbCache::getSampleSchema();
        return SqlHelper::getVendorSql($sql, $vendor);
    }

    public function setUp()
    {
        // $this->dbu = new DbUtilPdo(new \PDO("sqlite::memory:"));
        if (!defined("MM_SIMPLECACHE_PDO_JSON_CONFIG")) {
            die("MM_SIMPLECACHE_PDO_JSON_CONFIG not defined");
        }
        $this->dbu = new DbUtilPdo(
            json_decode(MM_SIMPLECACHE_PDO_JSON_CONFIG, true)
        );

        $sql = $this->getTestTableSql(MM_SIMPLECACHE_DB_VENDOR);
        $this->dbu->getResource()->exec($sql);

        $this->cache = new DbCache($this->dbu);
    }

    public function testSimpleCacheWorks()
    {
        $db = $this->dbu;
        $cache = $this->cache;
        $id = 'foo';

        $this->assertEquals(0, $db->fetchCount('_simple_cache'));
        $this->assertFalse($cache->hasItem($id));

        $cache->setItem($id, 'bar');

        $this->assertEquals(1, $db->fetchCount('_simple_cache'));

        $this->assertTrue($cache->hasItem($id));

        // getItem api
        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertEquals('bar', $item);
        $this->assertTrue($success === true);

        // manualne znizime valid_until
        $db->update('_simple_cache', ['valid_until' => 123], '1=1');

        // samotny zaznam tam logicky ostava
        $this->assertEquals(1, $db->fetchCount('_simple_cache'));

        // ale has item uz vrati false, lebo zaznam je stary
        $this->assertFalse($cache->hasItem($id));

        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertNull($item);
        $this->assertTrue($success === false);

        // ak zmazeme veci, tak z pohladu cache api je to rovnake ako ked je stary
        $db->delete('_simple_cache', '1=1');

        $this->assertFalse($cache->hasItem($id));

        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertNull($item);
        $this->assertTrue($success === false);

    }

    public function testGarbageCollectionWorks()
    {
        $db = $this->dbu;
        $cache = $this->cache;
        $id = 'foo';

        $cache->setItem($id, 'bar');

        // manualne znizime valid_until
        $db->update('_simple_cache', ['valid_until' => 123], '1=1');

        $cache->garbageCollect(1, 1);

        $this->assertEmpty($db->fetchCount($cache->getTableName()));
        $success = null;
        $this->assertNull($cache->getItem($id, $success));
        $this->assertFalse($success);
    }

    public function testCustomSerialize()
    {
        $db = $this->dbu;
        $cache = $this->cache;
        $id = 'foo';

        $cache->setSerialize(function($v){ return 'x';});
        $cache->setUnserialize(function($v){ return 'y';});

        $cache->setItem($id, 'bar');

        $all = $db->fetchAll('*', '_simple_cache');
        $this->assertEquals('x', $all[0]['data']);

        $this->assertEquals('y', $cache->getItem($id));
    }

    public function testNamespaceWorks()
    {
        $db = $this->dbu;
        $cache = $this->cache;
        $ns = "some";
        $id = 'foo';

        $cache->setNamespace($ns);
        $cache->setItem($id, 'bar');

        $all = $db->fetchAll("*", '_simple_cache');

        $this->assertEquals("$ns$id", $all[0]['id']);

        $cache->removeItem($id);

        $this->assertEquals(0, $db->fetchCount('_simple_cache'));
    }
}