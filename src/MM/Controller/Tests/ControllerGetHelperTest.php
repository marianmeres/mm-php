<?php declare(strict_types=1);

namespace MM\Controller\Tests;

use MM\Controller\Tests\Controller\SimpleController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-controller
 */
final class ControllerGetHelperTest extends TestCase
{
	//
	// Out-of-the-box helpers
	//

	public function testServerHelperWorks()
	{
		$c = new SimpleController();

		$this->assertInstanceOf('\MM\Controller\Helper\Server', $c->server());

		$this->assertFalse($c->server()->isAjax());
		$this->assertFalse($c->server()->isPost());
		$this->assertNull($c->server()->remoteIp());

		// mock
		$c = new SimpleController([]);
		$c->params()
			->_SERVER()
			->exchangeArray([
				'REQUEST_METHOD' => 'POST',
				'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
				'HTTP_X_FORWARDED_FOR' => '2',
				'REMOTE_ADDR' => '3',
			]);

		$this->assertTrue($c->server()->isAjax());
		$this->assertTrue($c->server()->isPost());
		$this->assertEquals('2', $c->server()->remoteIp());
	}

	public function testServerIsHttpsWorks()
	{
		$c = new SimpleController();
		$this->assertFalse($c->server()->isHttps());

		$c->params()
			->_SERVER()
			->exchangeArray([
				'HTTPS' => 'ON',
			]);

		$this->assertTrue($c->server()->isHttps());
	}
}
