<?php
namespace MM\View\Helper;

require_once __DIR__ . "/../_bootstrap.php";

/**
 * @group mm-view
 */
class HeadTitleTest extends \PHPUnit_Framework_TestCase
{
    public function testHeadTitleIsEmptyByDefault()
    {
        $h = new HeadTitle;
        $this->assertEquals("", (string) $h);
    }

    public function testHeadTitleWorks()
    {
        $h = new \MM\View\Helper\HeadTitle;
        $h->setSeparator(":");
        $h->append(">");
        $h->append(">", false);
        $h->prepend("pre");
        $h->append("post");

        $exp = "pre:&gt;:>:post";
        $this->assertEquals($exp, (string) $h);

        $h->setContainer(array());
        $this->assertEquals("", (string) $h);

        $h->append("a")->append("b")->reverse();
        $this->assertEquals("b:a", (string) $h);
    }

}
