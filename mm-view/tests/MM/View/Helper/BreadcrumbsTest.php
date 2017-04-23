<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class BreadcrumbsTest extends \PHPUnit_Framework_TestCase
{
    public function testBreadcrumbAcceptsCorrectArraysOnly()
    {
        $h = new Breadcrumbs();

        $h->append(['label' => 'home', 'href' => '/']);
        $h->append(['label' => 'blog', 'href' => '/blog/']);

        try {
            $h->append('foo');
            $this->fail('Should have failed on wrong data type');
        } catch(\MM\View\Exception $e){}

        try {
            $h->append(['label' => '', 'href' => '/']);
            $this->fail('Should have failed on empty label');
        } catch(\MM\View\Exception $e){}
    }

    public function testRemoveDupesWorks()
    {
        $h = new Breadcrumbs();

        $h->append(['label' => 'blog', 'href' => '/blog/']);
        $h->append(['label' => 'home', 'href' => '/']);
        $h->prepend(['label' => 'home', 'href' => '/']);

        $this->assertCount(3, $h);

        $h->removeDuplicateEntries();

        $this->assertCount(2, $h);

        $this->assertEquals('home', $h->getContainer()[0]['label']);
        $this->assertEquals('blog', $h->getContainer()[1]['label']);

        // reverse check
        $h->reverse();

        $this->assertEquals('blog', $h->getContainer()[0]['label']);
        $this->assertEquals('home', $h->getContainer()[1]['label']);
    }

    public function testToStringWorks()
    {
        $h = new Breadcrumbs();

        $h->append(['label' => 'home', 'href' => '/', 'data-foo' => 'one']);
        $h->append(['label' => 'blog', 'href' => '/blog/', 'data-foo' => 'two']);

        $h = "$h";
        //prx($h);

        $this->assertEquals(2, substr_count($h, " data-foo"));
        $this->assertEquals(1, substr_count($h, " data-foo='one'"));
        $this->assertEquals(1, substr_count($h, " data-foo='two'"));
    }

}
