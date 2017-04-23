<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class LinkRelTest extends \PHPUnit_Framework_TestCase
{
    public function testBreadcrumbAcceptsCorrectArraysOnly()
    {
        $h = new LinkRel();

        $h->append(['rel' => 'alternate', 'href' => 'foo']);
        $h->append(['rel' => 'alternate', 'href' => 'foo2']);
        $h->append(['href' => 'foo3', 'rel' => 'alternate', "type"=>"application/rss+xml" ]);
        $h->append(['href' => 'foo2', 'rel' => 'alternate']);

        $expected = implode("\n", [
            "<link rel='alternate' href='foo' />",
            "<link rel='alternate' href='foo2' />",
            "<link rel='alternate' href='foo3' type='application/rss+xml' />",
        ]);

        $this->assertEquals($expected, trim("$h"));
    }

}
