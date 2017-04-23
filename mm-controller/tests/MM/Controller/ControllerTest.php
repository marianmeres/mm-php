<?php
namespace MM\Controller;

require_once __DIR__ . "/_bootstrap.php";

require_once __DIR__ . "/_files/SimpleController.php";
require_once __DIR__ . "/_files/ObDisabledController.php";
require_once __DIR__ . "/_files/ControllerHelper.php";

/**
 * @group mm-controller
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testActionParamIsNotDefinedByDefault()
    {
        $c = new \SimpleController(array());
        $this->assertNull($c->params()->_action);
        $this->assertEquals('index', $c->params()->get('_action', 'index'));
    }

    public function testSettingRequestWorks()
    {
        $c = new \SimpleController(array('_action'=>'some'));
        $this->assertEquals('some', $c->params()->_action);
    }

    public function testDispatchReturnsResponse()
    {
        $c = new \SimpleController(array('_action'=>'test'));
        $this->assertTrue($c->dispatch() instanceof Response);

        // tu nizsie je response dva krat... lebo dispatchujem druhy krat...
        $this->assertEquals('1test21test2', $c->dispatch()->__toString());

        $c->response()->reset();
        $this->assertEquals('1test2', $c->dispatch()->__toString()); // __invoke

        $c->response()->reset();
        $this->assertEquals('bar2', $c->dispatch('foo')->__toString()); // test manual dispatching
    }

    public function testInvalidActionThrows()
    {
        $c = new \SimpleController(array('_action'=>'wrong'));

        try {
            $c->dispatch();
        } catch (\MM\Controller\Exception $e) {
            $this->assertEquals(404, $e->getCode());
            return;
        }
        $this->fail("Was expecting to throw");
    }

    public function testInvalidMethodThrows()
    {
        $this->setExpectedException("\MM\Controller\Exception");
        $c = new \SimpleController();
        $c->someNotExisting();
    }

    public function testOutputViaResponseSegments()
    {
        $c = new \SimpleController();
        $this->assertEquals('abc2', $c->dispatch('segment')->__toString());
    }

    public function testOutputViaEchoAndResponseSegments()
    {
        $c = new \SimpleController();
        $this->assertEquals('abcecho2', $c->dispatch('segment-and.echo')->__toString());
    }

    public function testResetResponseOnDispatch()
    {
        $c = new \SimpleController();
        $this->assertEquals('', $c->dispatch('dispatch/reset')->__toString());
    }

    public function testRequestParamsHaveLowerPriorityOverDefinedUserlandOnes()
    {
        $c = new \SimpleController(array(
            'bull' => 'hovno', // bude ignorovane...
        ));
//        $this->assertEquals('shit', $c->getParam('bull'));
        $this->assertEquals('shit', $c->params()->bull);
    }

    public function testSettingParamsProgramaticallyHasPriorityOverRequestOrDefinedOnes()
    {
        $c = new \SimpleController(array(
            'bull' => 'hovno',
        ));

        $this->assertEquals('shit', $c->params()->bull);
        $c->params()->bull = 'hovno';
        $this->assertEquals('hovno', $c->params()->bull);
    }

    public function testGettingRequestWorks()
    {
        $request = array(
            'a' => '1',
            'b' => '2',
        );
        $c = new \SimpleController($request);

        $this->assertEquals(1, $c->params()->get('a'));
        $this->assertEquals(2, $c->params()->get('b'));

        // ak neznamy kluc, tak vraci default
        $this->assertEquals(null, $c->params()->get('c'));
        $this->assertEquals(3,    $c->params()->get('c', 3));
    }

    public function testSettingExceptionManuallyWorks()
    {
        $c = new \SimpleController(array(), array(
            'exception' => new Exception("bullshit")
        ));
        $this->assertTrue($c->getException() instanceof \Exception);
        $this->setExpectedException("MM\Controller\Exception");
        $c->dispatch();

    }

    public function testResponseHeadersAccessorWorks()
    {
        $c = new \SimpleController();
        $response = $c->dispatch("redir");

        $this->assertTrue($response->isRedirect());
        $this->assertEquals("http://nba.com", $response->getHeader("LOCATION")); // kluc bude normalizovany
    }

    public function testNotFoundSetProperResponseHeaderStatus()
    {
        $c = new \SimpleController();
        try {
            $c->dispatch("not-existing");
            $this->fail();
        } catch (Exception\PageNotFound $e) {}

        $this->assertTrue($c->response()->isNotFound());
    }

    public function testAssertingExpectedParamsWorks()
    {
        $this->setExpectedException("MM\Controller\Exception");
        $c = new \SimpleController();
        $c->assertExpectedParams(array('one'));
    }

    public function testAssertingExpectedParamsWorks2()
    {
        $params = array('one' => 1, 'two' => 2);
        $c = new \SimpleController($params);
        $actual = $c->assertExpectedParams(array_keys($params));

        $this->assertEquals($actual, $params);
    }

    public function testSettingHelperManualyWorks()
    {
        $c = new \SimpleController();
        $c->setHelper("xxx", new \ControllerHelper());

        $this->assertEquals('bar', $c->getHelper('xxx')->foo());
    }

    public function testOutputDisabledWorks()
    {
        $c = new \ObDisabledController();

        ob_start();
        $resp = $c->dispatch('index'); // priamo echuje

        $this->assertEquals('index', ob_get_clean());
        $this->assertEquals('', $resp->toString());

        // tu je dolezite, ze sa nic nestane (neskape php na headers already sent)
        $resp->output();
    }

    public function testOutputDisabledWorks2()
    {
        $c = new \ObDisabledController();

        ob_start();
        $resp = $c->dispatch('direct-response');

        $this->assertEquals('', ob_get_clean());
        $this->assertEquals('response', $resp->toString());

        //
        ob_start();
        $resp->output();
        $this->assertEquals('response', ob_get_clean());
    }

    public function testOutputDisabledWorks3()
    {
        $c = new \ObDisabledController();

        ob_start();
        $resp = $c->dispatch('static');

        $this->assertEquals('static', ob_get_clean());
        $this->assertEquals('', $resp->toString());

        // tu je dolezite, ze sa nic nestane (neskape php na headers already sent)
        $resp->output();
    }

    public function testOutputDisabledWorks4()
    {
        $c = new \ObDisabledController();

        ob_start();
        $resp = $c->dispatch('throw');

        $this->assertFalse(ob_get_clean());
        $this->assertTrue((bool) preg_match('/thrown/', $resp->toString()));
        $this->assertTrue($resp->isServerError());

        // phpunit thinks this is risky... don't know why
        $this->assertEquals(0, ob_get_level());
    }
}