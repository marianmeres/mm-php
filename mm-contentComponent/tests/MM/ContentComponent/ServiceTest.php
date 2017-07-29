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
class ServiceTest extends \PHPUnit_Framework_TestCase
{
    public $dir;

    public function setUp()
    {
        $this->dir = __DIR__ . "/_files/";

        @mkdir("$this->dir/tmp");
        copy("$this->dir/page-template.xml", "$this->dir/tmp/page.xml");
    }

    public function testMainApiServiceProxyWorks()
    {
        copy("$this->dir/page-template.md", "$this->dir/tmp/page.md");

        $mapper = new Mapper([
            'serializer' => new Serializer\HtmlComments,
            'dao' => new Dao\File([
                'base_dir' => "$this->dir/tmp",
                'extension' => 'md'
            ])
        ]);

        $service = new Service([
            'mapper' => $mapper
        ]);

        $page = $service->find('page');

        $this->assertTrue($page instanceof Model);
        $this->assertEquals($page->foo, 'bar');
        $this->assertEquals($page->baz, 'bat');
        $this->assertEquals($page->main, 'this is main content');

        // delete, save
        unlink("$this->dir/tmp/page.md");
        $page->markDirty();
        $mapper->save($page);

        // and try again all
        $page = $mapper->find('page');

        $this->assertTrue($page instanceof Model);
        $this->assertEquals($page->foo, 'bar');
        $this->assertEquals($page->baz, 'bat');
        $this->assertEquals($page->main, 'this is main content');
    }
}