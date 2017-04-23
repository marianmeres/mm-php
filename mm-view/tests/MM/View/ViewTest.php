<?php
namespace MM\View;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-view
 */
class ViewTest extends \PHPUnit_Framework_TestCase
{
    public function testViewVarsCanBeSetAndRead()
    {
        $v = new View;
        $vars = $v->dump();
        $this->assertTrue(empty($vars));
        $v->a = 1;
        $this->assertEquals(1, $v->a);
        $vars = $v->dump();
        $this->assertFalse(empty($vars));
    }

    public function testAccessingUndefinedVarTriggersWarning()
    {
        $this->setExpectedException("\PHPUnit_Framework_Error");
        $v = new View;
        $v->some;
    }

    public function testValuesAreEscapedByDefault()
    {
        $v = new View(array(
            'vars' => array(
                'a' => ">",
            )
        ));
        $this->assertEquals("&gt;", $v->a);
    }

    public function testRawValueIsAccessibleViaRawMethod()
    {
        $v = new View;
        $v->a = ">";
        $this->assertEquals(">", $v->raw('a'));
    }

    public function testBasicRenderWorks()
    {
        $v = new View;
        $tpl = __DIR__ . "/_files/tpl/123.phtml";
        $this->assertEquals("123", $v->render($tpl));
    }

    public function testBasicRenderWorks2()
    {
        $v = new View;
        $v->a = 123;
        $tpl = __DIR__ . "/_files/tpl/a.phtml";
        $this->assertEquals("123", $v->render($tpl));
    }

    public function testThisWithinTemplateRefersToViewObjectAndOnlyPublicScopeIsAccessible()
    {
        $v = new View;
        $tpl = __DIR__ . "/_files/tpl/this.phtml";
        $this->assertEquals("1", $v->render($tpl));
    }

    public function testNonScalarTypesAreNotEscaped()
    {
        $v = new View;
        $v->a = new \stdClass();
        $v->b = array();

        // toto musi vratit normalne povodne veci
        $this->assertTrue($v->a instanceof \stdClass);
        $this->assertEquals(array(), $v->b);

    }

    public function testSetHelperManuallyWorks()
    {
        require_once __DIR__ . "/_files/BullShitHelper.php";
        $v = new View();
        $v->setHelper("xxx", new \BullShitHelper());

        $this->assertEquals('bullshit', $v->getHelper('xxx')->__invoke());
    }

    public function testSetHelperManuallyWorks2()
    {
        require_once __DIR__ . "/_files/BullShitHelper.php";
        $v = new View();
        $this->assertEquals('bullshit', $v->getHelper('xxx', '\BullShitHelper')->__invoke());
    }

    public function testHeadTitleHelperWorks()
    {
        $v = new View();
        $t = $v->headTitle("some");

        $this->assertEquals('some', $t->toString());
    }

    public function testHeadScriptSrcHelperWorks()
    {
        $v = new View();
        $t = $v->headScriptSrc("some");

        $this->assertEquals("<script src='some'></script>", trim($t->toString()));
    }

    public function testHeadCssSrcHelperWorks()
    {
        $v = new View();
        $t = $v->headCssSrc("some");

        $this->assertEquals("<link href='some' rel='stylesheet'>", trim($t->toString()));
    }
}