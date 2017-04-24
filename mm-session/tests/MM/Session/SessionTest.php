<?php
/**
 * @author Marian Meres
 */
namespace MM\Session;

use MM\Controller\Response;

use MM\Session\Session;
use MM\Session\Exception;
use MM\Session\SaveHandler\Mock;

require_once __DIR__ . "/_bootstrap.php";

/**
 * @group mm-session
 */
class SessionTest extends \PHPUnit_Framework_TestCase
{

    /**
     * len bazalne...
     */

    public function setUp()
    {
        Session::resetToOutOfTheBoxState($mock = true);
    }

    public function tearDown()
    {
        Session::resetToOutOfTheBoxState($mock = false);
    }

    public function testSanityCheck()
    {
        $s = Session::getNamespace();
        $s->foo = 'bar';

        $s2 = Session::getNamespace();
        $this->assertEquals('bar', $s2->foo);

        $id = Session::getId();
        $this->assertNotEmpty($id);

        Session::regenerateId();
        $this->assertNotSame($id, Session::getId());

        $this->assertEquals('bar', Session::getNamespace()->foo);
        $this->assertTrue(!isset(Session::getNamespace("asdf")->foo));
    }

    public function testNamespacesAreNestedInRootContainerInternally()
    {
        $s = Session::getNamespace();
        $s->foo = 'bar';

        $s = Session::getNamespace('baz');
        $s->foo = 'bar';

        $nsUsr = Session::getNsUsr();

        // raw access
        $this->assertEquals('bar', $_SESSION[$nsUsr]['default']['foo']);
        $this->assertEquals('bar', $_SESSION[$nsUsr]['baz']['foo']);
    }

    public function testRootNamespaceContainerIsAccessibleIfNsIsNull()
    {
        $s = Session::getNamespace();
        $s->foo = 'bar';

        $s = Session::getNamespace('baz');
        $s->foo = 'bar';

        $root = Session::getNamespace(null);

        $this->assertEquals('bar', $root['default']['foo']);
        $this->assertEquals('bar', $root['baz']['foo']);

        $this->assertEquals('bar', $root->default->foo);
        $this->assertEquals('bar', $root->baz->foo);
    }

}