<?php
/**
 * @author Marian Meres
 */
namespace MM\Application;


require_once __DIR__ . "/_bootstrap.php";


/**
 * @group mm-application
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testApplicationAcceptsInitCallbacksWhichAreNotExecutedDirectly()
    {
        $a = new Application();
        $a->init("hoho", function ($app) {
            throw new \Exception("I must not be thrown");
        });
    }

    public function testApplicationAcceptsCallbackMapAtConstructor()
    {
        $map = array(
            'a' => function ($app) {echo 1;},
            'b' => function ($app) {echo 2;},
            'c' => function ($app) {echo 3;},
        );
        $a = new Application($map);
        ob_start();
            $a->bootstrap();
        $ob = ob_get_clean();
        $this->assertEquals("123", $ob);
    }

    public function testBootstrapingMayBeSelective()
    {
        $map = array(
            'a' => function ($app) {echo 1;},
            'b' => function ($app) {echo 2;},
            'c' => function ($app) {echo 3;},
        );
        $a = new Application($map);
        ob_start();
            $a->bootstrap(array("a", "c"));
        $ob = ob_get_clean();
        $this->assertEquals("13", $ob);
    }

    public function testStaticFactoryWorks()
    {
        $map = array(
            'a' => function ($app) {echo 1;},
            'b' => function ($app) {echo 2;},
            'c' => function ($app) {echo 3;},
        );
        ob_start();
            Application::factory($map);
        $ob = ob_get_clean();
        $this->assertEquals("123", $ob);
    }

    public function testBootstrapMethodMustTriggerInitCallbacksWhichAreExecutedOnlyOnce()
    {
        $a = new Application();
        $a->init("hoho", function ($app) {
            echo "cool";
            return 123;
        });

        ob_start();
            $a->bootstrap();
            $a->bootstrap(); // callback will NOT be called twice
        $ob = ob_get_clean();

        $this->assertEquals("cool", $ob);

        $this->assertEquals(123, $a->init("hoho"));
    }

    public function testInitializingUninitializedCallbackThrows()
    {
        $this->setExpectedException("MM\Application\Exception");
        $a = new Application();
        $a->init("i was not defined");
    }

    public function testRegistreringAcceptsOnlyCallbacks()
    {
        $this->setExpectedException("PHPUnit_Framework_Error");
        $a = new Application();
        $a->init("bla", 123);
    }

    public function testInitCallbacksRecievesCurrentApplicationInstanceAsAnArgument()
    {
        $a = new Application();

        $a->init("a", function ($app) {
            echo "a";
            return 1;
        });

        $a->init("b", function ($app) {
            // toto echne result a "a" callbacku
            echo $app->init("a");
            return 2;
        });

        ob_start();
            $a->bootstrap();
        $ob = ob_get_clean();

        $this->assertEquals("a1", $ob);
        $this->assertEquals(2, $a->get("b")); // get alias in action
    }

    // factory controller a decompose regquest netestujeme
}