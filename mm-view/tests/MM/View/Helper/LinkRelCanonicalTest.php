<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";

/**
 * @group mm-view
 */
class LinkRelCanonicalTest extends \PHPUnit_Framework_TestCase
{
    public function testLinkRelCanonicalWorks()
    {
        $h = new \MM\View\Helper\LinkRelCanonical();

        $h->setHref('/will-be-overwritten');
        $h->setHref('http://server/foo/');

        $this->assertEquals("<link rel='canonical' href='http://server/foo/'/>\n", "$h");

        $h->setHref(null);
        $this->assertEquals("", "$h");
    }

}
