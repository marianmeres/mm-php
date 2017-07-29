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
class MapperTest extends \PHPUnit_Framework_TestCase
{
    public $dir;

    public function setUp()
    {
        $this->dir = __DIR__ . "/_files/";

        @mkdir("$this->dir/tmp");
        copy("$this->dir/page-template.xml", "$this->dir/tmp/page.xml");
    }

    public function testFindWorks()
    {
        $mapper = new Mapper([
            'serializer' => new Serializer\Xml,
            'dao' => new Dao\File(['base_dir' => "$this->dir/tmp"])
        ]);

        $page = $mapper->find('page');

        $this->assertTrue($page instanceof Model);
        $this->assertEquals($page->title, 'some');

        // test attr getter (two alternative notations)
        $this->assertEquals($page->attr('body')['format'], 'markdown');
        $this->assertEquals($page->attr('body', 'format'), 'markdown');

        // test attr setter
        $page->attr('body', 'format', 'txt');
        $this->assertEquals($page->attr('body')['format'], 'txt');
    }

    public function testFindingNotExistingReturnsNull()
    {
        $mapper = new Mapper([
            'serializer' => new Serializer\Xml,
            'dao' => new Dao\File(['base_dir' => "$this->dir/tmp"])
        ]);

        $page = $mapper->find('fooo', false);
        $this->assertNull($page);
    }

    public function testSaveWorks()
    {
        $mapper = new Mapper([
            'serializer' => new Serializer\Xml,
            'dao' => new Dao\File(['base_dir' => "$this->dir/tmp"])
        ]);

        $model = new Model([
            'id' => 'foo',
            'foo' => 123,
            'body' => 'some <b>html</b> or markdown'
        ]);

        // optional type
        $model->componentAttributes['type'] = 'foo_type';

        $model->initDataAttributes([
            'body' => ['format' => 'markdown']
        ]);

        $model->foo = 123;

        @unlink("$this->dir/tmp/foo.xml");
        $model->markDirty();
        $mapper->save($model);
        $this->assertTrue(file_exists("$this->dir/tmp/foo.xml"));

        //
        $xml = simplexml_load_file("$this->dir/tmp/foo.xml");
        $this->assertEquals(123, trim((string) $xml->foo[0]));
        $this->assertEquals('markdown', (string) $xml->body['format']);
        $this->assertEquals('foo_type', (string) $xml['type']);
    }

    public function testRemoveWorks()
    {
        $mapper = new Mapper([
            'serializer' => new Serializer\Xml,
            'dao' => new Dao\File(['base_dir' => "$this->dir/tmp"])
        ]);

        $mapper->delete('page');
        $this->assertNull($mapper->find('page', false));
        $this->assertFalse(file_exists("$this->dir/tmp/page.xml"));
    }

    public function testMarkdownSerializerWorks()
    {
        copy("$this->dir/page-template.md", "$this->dir/tmp/page.md");

        $mapper = new Mapper([
            'serializer' => new Serializer\HtmlComments,
            'dao' => new Dao\File([
                'base_dir' => "$this->dir/tmp",
                'extension' => 'md'
            ])
        ]);

        $page = $mapper->find('page');

        $this->assertTrue($page instanceof Model);
        $this->assertEquals($page->foo, 'bar');
        $this->assertEquals($page->baz, 'bat');
        $this->assertEquals($page->main, 'this is main content');

        // delete, save
        unlink("$this->dir/tmp/page.md");
        $page->markDirty(); // aby nizsi save nieco urobil
        $mapper->save($page);

        // and try again all
        $page = $mapper->find('page');

        $this->assertTrue($page instanceof Model);
        $this->assertEquals($page->foo, 'bar');
        $this->assertEquals($page->baz, 'bat');
        $this->assertEquals($page->main, 'this is main content');
    }
}