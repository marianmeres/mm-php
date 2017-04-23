<?php
namespace MM\View\Helper;

use MM\View\View;

require_once __DIR__ . "/../_bootstrap.php";


/**
 * @group mm-view
 */
class ContainerOfStringsTest extends \PHPUnit_Framework_TestCase
{
    public function testContainerIsEmptyByDefault()
    {
        $h = new ContainerOfStrings;
        $this->assertEquals("", (string) $h);
    }

    public function testHelperWorks()
    {
        $h = new ContainerOfStrings;
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

    public function testUniqueWorks()
    {
        $h = new ContainerOfStrings;
        $h(array(".",".",".")); // deps will be ignored
        $this->assertEquals(".", (string) $h);

        $h = new ContainerOfStrings;
        $h->setUnique(false);
        $h(array(".",".","."));
        $this->assertEquals("...", (string) $h);
    }

    public function testEscapeWorks()
    {
        $h = new ContainerOfStrings;
        $h->setUnique(false);
        $h(">");
        $h(">", "prepend", false);
        $h(">", "append", false);
        $this->assertEquals(">&gt;>", (string) $h);
    }

    public function testEscapeIsOverridablePerMethodCall()
    {
        $h = new ContainerOfStrings;
        $h->setEscape(false);
        $this->assertEquals(">", (string) $h(">"));

        $h = new ContainerOfStrings;
        $h->setEscape(false);
        $this->assertEquals("&gt;", (string) $h(">", "append", $escape = true)); // forcujeme escape

    }

    public function testLabeledContainerWorks()
    {
        $h = new LabeledContainersOfStrings();
        $body = $h->get('body');
        $body->append(['a', 'b', 'a']);
        $this->assertEquals('a b', $body->toString());

        $view = new View();
        $view->cssClassFor('some', ['a', 'a']);
        $view->cssClassFor('some', ['b', 'c']);
        $this->assertEquals('a b c', $view->cssClassFor('some'));
    }
}
