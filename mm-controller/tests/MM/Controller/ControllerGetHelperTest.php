<?php
namespace MM\Controller;

require_once __DIR__ . "/_bootstrap.php";

require_once __DIR__ . "/_files/SimpleController.php";
require_once __DIR__ . "/_files/ControllerHelper.php";

/**
 * @group mm-controller
 */
class ControllerGetHelperTest extends \PHPUnit_Framework_TestCase
{
    //
    // Out-of-the-box helpers
    //

    public function testServerHelperWorks()
    {
        $c = new \SimpleController();

        $this->assertInstanceOf('\MM\Controller\Helper\Server', $c->server());

        $this->assertFalse($c->server()->isAjax());
        $this->assertFalse($c->server()->isPost());
        $this->assertNull($c->server()->remoteIp());

        // mock
        $c = new \SimpleController(array());
        $c->params()->_SERVER()->exchangeArray(array(
            'REQUEST_METHOD'        => 'POST',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            'HTTP_X_FORWARDED_FOR'  => '2',
            'REMOTE_ADDR'           => '3'
        ));

        $this->assertTrue($c->server()->isAjax());
        $this->assertTrue($c->server()->isPost());
        $this->assertEquals('2', $c->server()->remoteIp());
    }

    public function testServerIsHttpsWorks()
    {
        $c = new \SimpleController();
        $this->assertFalse($c->server()->isHttps());

        $c->params()->_SERVER()->exchangeArray(array(
            'HTTPS' => 'ON',
        ));

        $this->assertTrue($c->server()->isHttps());
    }
}