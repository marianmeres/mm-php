<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Session\Session;
// use MM\Util\SqlHelper;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-session
 */
class SaveHandlerFlatFileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->dir = __DIR__ . "/_tmp";
        @mkdir($this->dir);

        foreach (glob("$this->dir/*") as $f) {
            if (is_dir($f)) {
                foreach (glob("$f/*") as $f2) {
                    unlink($f2);
                }
                rmdir($f);
            } else {
                unlink($f);
            }
        }

        // defaultne optiony
        $this->sh = new SaveHandler\FlatFile(array(
            'dir' => $this->dir
        ));

        ini_set('session.gc_maxlifetime', 1440);
    }

    public function countFlatFiles()
    {
        return count(glob("$this->dir/*"));
    }

    public function testWriteWritesToFile()
    {
        $this->assertEquals(0, $this->countFlatFiles());
        $this->sh->write(123, 'foo');
        $this->assertEquals(1, $this->countFlatFiles());
        // a skusime ci existuje spravny fajl
        $this->assertTrue(file_exists(
            "$this->dir/{$this->sh->prefix}123"
        ));
    }

    public function testWriteUpdatesFileContents()
    {
        $f = "$this->dir/{$this->sh->prefix}123";
        $this->sh->write(123, 'foo');
        $this->assertEquals('foo', file_get_contents($f));
        $this->sh->write(123, 'bar');
        $this->assertEquals('bar', file_get_contents($f));
    }

    public function testDestroyDeletesFile()
    {
        $this->assertEquals(0, $this->countFlatFiles());
        $this->sh->write(123, 'foo');
        $this->assertEquals(1, $this->countFlatFiles());
        $this->sh->destroy(123);
        $this->assertEquals(0, $this->countFlatFiles());

    }

    public function testGarbageCollectWorks()
    {
        $this->sh->write(123, 'foo');
        $this->assertEquals(1, $this->countFlatFiles());
        $this->sh->gc(0);
        $this->assertEquals(0, $this->countFlatFiles());
    }

}