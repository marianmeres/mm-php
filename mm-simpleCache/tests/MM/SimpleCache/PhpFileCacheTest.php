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
class PhpFileCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PhpFileCache
     */
    public $cache;

    public $dir;

    protected function _rrmdir($dir)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if (preg_match('/^\.\.?$/', $path->getFilename())) {
                continue;
            }
            if ($path->isDir()) {
                rmdir($path->getPathname());
            } else {
                unlink($path->getPathname());
            }
        }

        unset($iterator); // needed to avoid "permission denied" below
        rmdir($dir);
    }

    public function setUp()
    {
        $this->dir = __DIR__ . "/tmp/";
        $this->_rrmdir($this->dir);
        mkdir($this->dir);
        $this->cache = new PhpFileCache($this->dir);
    }

    public function testPhpFileCacheWorks()
    {
        $cache = $this->cache;
        $id = 'foo';
        $filename = "$this->dir/foo.php";

        $this->assertFalse($cache->hasItem($id));

        $cache->setItem($id, 'bar');

        $this->assertEquals(1, count(glob("$this->dir/*.php")));
        $this->assertTrue($cache->hasItem($id));


        // getItem api
        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertEquals('bar', $item);
        $this->assertTrue($success === true);

        // manualne znizime valid_until
        touch($filename, 123);
        clearstatcache(false, $filename);

        // samotny zaznam tam logicky ostava
        $this->assertTrue(file_exists($filename));

        // ale has item uz vrati false, lebo zaznam je stary
        $this->assertFalse($cache->hasItem($id));

        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertNull($item);
        $this->assertTrue($success === false);

        // ak zmazeme veci, tak z pohladu cache api je to rovnake ako ked je stary
        unlink($filename);

        $this->assertFalse($cache->hasItem($id));

        $success = null;
        $item = $cache->getItem($id, $success);
        $this->assertNull($item);
        $this->assertTrue($success === false);
    }

    public function testGarbageCollectionWorks()
    {
        $cache = $this->cache;
        $id = 'foo';
        $filename = "$this->dir/foo.php";

        $cache->setItem($id, 'bar');
        $this->assertTrue($cache->hasItem($id));

        // manualne znizime valid_until
        touch($filename, 123);
        clearstatcache(false, $filename);

        $cache->garbageCollect(1, 1);
        $this->assertFalse($cache->hasItem($id));

        $this->assertEmpty(glob("$this->dir/*.php"));
        $success = null;
        $this->assertNull($cache->getItem($id, $success));
        $this->assertFalse($success);
    }

    public function testNamespaceWorks()
    {
        $cache = $this->cache;
        $ns = "some";
        $id = 'foo';

        $cache->setNamespace($ns);
        $cache->setItem($id, 'bar');

        $glob = glob("$this->dir/*.php");
        $this->assertEquals(1, count($glob));
        $this->assertTrue(!!preg_match("/$ns$id\.php$/", $glob[0]));

        $cache->removeItem($id);
        $this->assertEquals(0, count(glob("$this->dir/*.php")));

    }
}