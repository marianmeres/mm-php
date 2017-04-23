<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class OpenGraphDataTest extends \PHPUnit_Framework_TestCase
{
    public function testOpenGraphDataWorks()
    {
        $h = new OpenGraphData();

        $h->add([
            'fb:app_id' => 123,
            'title' => 'foo',
            'og:url' => 'bar' // "og:" prefix is optional
        ]);

        $h->add('description', 'baz'); // will be overidden
        $h->add('og:description', 'bat');
        $h->add('og:description', 'kokos', false); // will not overwrite

        $this->assertCount(4, $h);

        $expected = implode("\n", [
            "<meta property='fb:app_id' content='123'/>",
            "<meta property='og:title' content='foo'/>",
            "<meta property='og:url' content='bar'/>",
            "<meta property='og:description' content='bat'/>",
        ]);

        $this->assertEquals($expected, trim("$h"));

        // zmenime title
        $h->add(['title' => 'new']); // via array
        $this->assertTrue((bool) preg_match("/property='og:title' content='new'/", "$h"));

        $h->add('title', 'new2'); // via k v
        $this->assertTrue((bool) preg_match("/property='og:title' content='new2'/", "$h"));

        // zmenime, ale nedovolime prepisat
        $h->add(['title' => 'new3'], null, false); // via array
        $this->assertTrue((bool) preg_match("/property='og:title' content='new2'/", "$h"));
        $h->add('title', 'new3', false); // via k v
        $this->assertTrue((bool) preg_match("/property='og:title' content='new2'/", "$h"));
    }

    public function testUnknownPropertyThrows()
    {
        $this->setExpectedException('\MM\View\Exception');
        $h = new OpenGraphData();
        $h->add('foo', 'bat');
    }


}
