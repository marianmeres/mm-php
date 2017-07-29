<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 11:47
 */
namespace MM\ContentComponent;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-contentComponent
 */
class DaoFileTest extends \PHPUnit_Framework_TestCase
{
    public $dir;

    public function setUp()
    {
        $this->dir = __DIR__ . "/_files/";

        @mkdir("$this->dir/tmp");
        copy("$this->dir/page-template.xml", "$this->dir/tmp/page.xml");
    }

    public function testGettingFilenameViaFilenameFactoryWorks()
    {
        $dao = new Dao\File([
            'base_dir' => "$this->dir/tmp",
            'filename_factory' => function($dao, $cid) {
                return "/custom/$cid.xml";
            }
        ]);

        $this->assertEquals($dao->getFilename('page'), '/custom/page.xml');
    }

    public function testGettingFilenameWorks()
    {
        $dao = new Dao\File(['base_dir' => "$this->dir/tmp"]);

        $this->assertEquals($dao->getFilename('page'), "$this->dir/tmp/page.xml");

        $dao->extension = 'abc';
        $this->assertEquals($dao->getFilename('page'), "$this->dir/tmp/page.abc");
    }

    public function testCreateWorks()
    {
        $dao = new Dao\File(['base_dir' => "$this->dir/tmp"]);
        $dao->create('page', 'xxx');
        $this->assertEquals(file_get_contents("$this->dir/tmp/page.xml"), 'xxx');
    }

    public function testReadWorks()
    {
        $dao = new Dao\File(['base_dir' => "$this->dir/tmp"]);

        file_put_contents("$this->dir/tmp/page.xml", 'xxx');

        $this->assertEquals($dao->read('page'), 'xxx');
    }

    public function testUpdateWorks()
    {
        $dao = new Dao\File(['base_dir' => "$this->dir/tmp"]);

        file_put_contents("$this->dir/tmp/page.xml", 'xxx');

        $dao->update('page', 'yyy');

        $this->assertEquals(file_get_contents("$this->dir/tmp/page.xml"), 'yyy');
    }

    public function testDeleteWorks()
    {
        $dao = new Dao\File(['base_dir' => "$this->dir/tmp"]);

        $this->assertTrue(file_exists("$this->dir/tmp/page.xml"));
        $dao->delete('page');
        $this->assertFalse(file_exists("$this->dir/tmp/page.xml"));
    }

    public function testFetchingAvailableComponentIdsWorks()
    {
        $dao = new Dao\File(['base_dir' => $this->dir]);

        //
        $files = $dao->fetchAvailableComponentIds(['recursive' => true]);
        $this->assertGreaterThanOrEqual(4, count($files));

        /*
        tree
        ├── a.xml
        ├── b.xml
        └── sub
            ├── c.xml
            └── sub2
                └── d.xml
                └── e.xml
        */

        //
        $dao->setBaseDir("$this->dir/tree");
        $files = $dao->fetchAvailableComponentIds(['recursive' => false]);
        $expected = ['a', 'b'];
        $this->assertEquals($expected, $files);

        //
        $dao->setBaseDir("$this->dir/tree/");
        $files = $dao->fetchAvailableComponentIds(['recursive' => true]);
        $expected = ['a', 'b', 'sub/c', 'sub/sub2/d', 'sub/sub2/e'];
        $this->assertEquals($expected, $files);

        //
        $dao->setBaseDir("$this->dir/tree/sub");
        $files = $dao->fetchAvailableComponentIds(['recursive' => false]);
        $expected = ['c'];
        $this->assertEquals($expected, $files);

        //
        $dao->setBaseDir("$this->dir/tree/sub");
        $files = $dao->fetchAvailableComponentIds(['recursive' => true]);
        $expected = ['c', 'sub2/d', 'sub2/e'];
        $this->assertEquals($expected, $files);

        //
        $dao->setBaseDir("$this->dir/tree/sub/sub2");
        $files = $dao->fetchAvailableComponentIds(['recursive' => true]);
        $expected = ['d', 'e'];
        $this->assertEquals($expected, $files);
    }

}