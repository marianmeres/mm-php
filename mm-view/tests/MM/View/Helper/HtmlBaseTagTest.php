<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";

/**
 * @group mm-view
 */
class HtmlBaseTagTest extends \PHPUnit_Framework_TestCase
{
    public function testHtmlBaseTagWorks()
    {
        $h = new \MM\View\Helper\HtmlBaseTag();

        $h->setHref('/will-be-overwritten');
        $h->setHref('/foo');

        $this->assertEquals("<base href='/foo'/>\n", "$h");

        $h->setHref(null);
        $this->assertEquals("", "$h");
    }

}
