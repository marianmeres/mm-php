<?php declare(strict_types=1);

namespace MM\Controller\Tests;

use MM\Controller\Response;
use MM\Controller\Exception;
use MM\Controller\Tests\Controller\ControllerHelper;
use MM\Controller\Tests\Controller\ObDisabledController;
use MM\Controller\Tests\Controller\SimpleController;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-controller
 */
final class ControllerTest extends TestCase
{
	public function testSettingParamsAtConstructionWorks()
	{
		$c = new SimpleController(['foo' => 'bar']);
		$this->assertEquals('bar', $c->params()->foo);
	}

	public function testDispatchByArgWorks()
	{
		$this->assertEquals(
			'1test2',
			SimpleController::factory()
				->dispatch('test')
				->toString()
		);
	}

	public function testDispatchReturnsResponse()
	{
		$c = new SimpleController();
		$this->assertTrue($c->dispatch('test') instanceof Response);

		// tu nizsie je response dva krat... lebo dispatchujem druhy krat...
		$this->assertEquals('1test21test2', $c->dispatch('test')->__toString());

		$c->response()->reset();
		$this->assertEquals('1test2', $c->dispatch('test')->__toString()); // __invoke

		$c->response()->reset();
		$this->assertEquals('bar2', $c->dispatch('foo')->__toString()); // test manual dispatching
	}

	public function testInvalidActionThrows()
	{
		$c = new SimpleController();

		try {
			$c->dispatch('wrong');
		} catch (Exception $e) {
			$this->assertEquals(404, $e->getCode());
			return;
		}
		$this->fail('Was expecting to throw');
	}

	public function testInvalidMethodThrows()
	{
		$this->expectException(Exception::class);
		$c = new SimpleController();
		$c->someNotExisting();
	}

	public function testOutputViaResponseSegments()
	{
		$c = new SimpleController();
		$this->assertEquals('abc2', $c->dispatch('segment')->__toString());
	}

	public function testOutputViaEchoAndResponseSegments()
	{
		$c = new SimpleController();
		$this->assertEquals('abcecho2', $c->dispatch('segment-and.echo')->__toString());
	}

	public function testResetResponseOnDispatch()
	{
		$c = new SimpleController();
		$this->assertEquals('', $c->dispatch('dispatch/reset')->__toString());
	}

	public function testRequestParamsHaveLowerPriorityOverDefinedUserlandOnes()
	{
		$c = new SimpleController([
			'bull' => 'hovno', // bude ignorovane...
		]);
		//        $this->assertEquals('shit', $c->getParam('bull'));
		$this->assertEquals('shit', $c->params()->bull);
	}

	public function testSettingParamsProgramaticallyHasPriorityOverRequestOrDefinedOnes()
	{
		$c = new SimpleController([
			'bull' => 'hovno',
		]);

		$this->assertEquals('shit', $c->params()->bull);
		$c->params()->bull = 'hovno';
		$this->assertEquals('hovno', $c->params()->bull);
	}

	public function testGettingRequestWorks()
	{
		$request = [
			'a' => '1',
			'b' => '2',
		];
		$c = new SimpleController($request);

		$this->assertEquals(1, $c->params()->get('a'));
		$this->assertEquals(2, $c->params()->get('b'));

		// ak neznamy kluc, tak vraci default
		$this->assertEquals(null, $c->params()->get('c'));
		$this->assertEquals(3, $c->params()->get('c', 3));
	}

	public function testSettingExceptionManuallyWorks()
	{
		$c = new SimpleController(
			[],
			[
				'exception' => new Exception('bullshit'),
			]
		);
		$this->assertTrue($c->getException() instanceof \Exception);
		$this->expectException(Exception::class);
		$c->dispatch();
	}

	public function testResponseHeadersAccessorWorks()
	{
		$c = new SimpleController();
		$response = $c->dispatch('redir');

		$this->assertTrue($response->isRedirect());
		$this->assertEquals('http://nba.com', $response->getHeader('LOCATION')); // kluc bude normalizovany
	}

	public function testNotFoundSetProperResponseHeaderStatus()
	{
		$c = new SimpleController();
		try {
			$c->dispatch('not-existing');
			$this->fail();
		} catch (Exception\PageNotFound $e) {
		}

		$this->assertTrue($c->response()->isNotFound());
	}

	public function testAssertingExpectedParamsWorks()
	{
		$this->expectException(Exception::class);
		$c = new SimpleController();
		$c->assertExpectedParams(['one']);
	}

	public function testAssertingExpectedParamsWorks2()
	{
		$params = ['one' => 1, 'two' => 2];
		$c = new SimpleController($params);
		$actual = $c->assertExpectedParams(array_keys($params));

		$this->assertEquals($actual, $params);
	}

	public function testSettingHelperManualyWorks()
	{
		$c = new SimpleController();
		$c->setHelper('xxx', new ControllerHelper());

		$this->assertEquals('bar', $c->getHelper('xxx')->foo());
	}

	public function testOutputDisabledWorks()
	{
		$c = new ObDisabledController();

		ob_start();
		$resp = $c->dispatch('index'); // priamo echuje

		$this->assertEquals('index', ob_get_clean());
		$this->assertEquals('', $resp->toString());

		// tu je dolezite, ze sa nic nestane (neskape php na headers already sent)
		$resp->output();
	}

	public function testOutputDisabledWorks2()
	{
		$c = new ObDisabledController();

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
		$c = new ObDisabledController();

		ob_start();
		$resp = $c->dispatch('static');

		$this->assertEquals('static', ob_get_clean());
		$this->assertEquals('', $resp->toString());

		// tu je dolezite, ze sa nic nestane (neskape php na headers already sent)
		$resp->output();
	}

	public function testOutputDisabledWorks4()
	{
		// phpunit hack... read below...
		$initialObLevel = ob_get_level();

		$c = new ObDisabledController();

		// ob_start();
		$resp = $c->dispatch('throw');

		$this->assertFalse(ob_get_clean());
		$this->assertTrue((bool) preg_match('/thrown/', $resp->toString()));
		$this->assertTrue($resp->isServerError());

		$this->assertEquals(0, ob_get_level());

		// because PhpUnit has its own output buffering, and our controller on Exception
		// catch clears the ob stack, we need to get here to initial value...
		// Test code or tested code did not (only) close its own output buffers
		while ($initialObLevel && $initialObLevel--) {
			ob_start();
		}
	}
}
