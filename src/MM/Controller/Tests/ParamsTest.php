<?php declare(strict_types=1);

namespace MM\Controller\Tests;

use MM\Controller\Params;
use MM\Controller\Parameters;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-controller
 */
final class ParamsTest extends TestCase
{
	protected function setUp(): void
	{
		$_COOKIE = [];
	}

	protected function tearDown(): void
	{
		$_COOKIE = [];
	}

	public function testCleanInstanceDefaultsToCleanContainers()
	{
		$p = new Params();

		// nizsie vsetky pocty su 0, ale davame to takto
		// _params
		$this->assertTrue(is_array($p->get()));
		$this->assertEquals(count(array_merge($_GET, $_POST)), count($p->get()));

		// _GET
		$this->assertTrue($p->_GET() instanceof Parameters);
		$this->assertEquals(count($_GET), count($p->_GET()));

		// _POST
		$this->assertTrue($p->_POST() instanceof Parameters);
		$this->assertEquals(count($_POST), count($p->_POST()));

		// _SERVER
		$this->assertTrue($p->_SERVER() instanceof Parameters);
		$this->assertEquals($_SERVER, $p->_SERVER()->getArrayCopy());
	}

	public function testAccessingParamsWorks()
	{
		$g = ['a' => 1, 'b' => 2];
		$p = ['c' => 3, 'd' => 4];
		$s = ['e' => 5, 'f' => 6];
		$x = ['g' => 7, 'h' => 8];

		$p = new Params([
			'_GET' => $g,
			'_POST' => $p,
			'_SERVER' => $s,
			'params' => $x,
		]);

		$this->assertEquals(1, $p->a);
		$this->assertEquals(2, $p->b);
		$this->assertEquals(3, $p['c']);
		$this->assertEquals(4, $p['d']);

		// server sa nemerguje medzi parametre
		$this->assertEquals(null, $p->get('e'));
		$this->assertEquals('xx', $p->get('f', 'xx')); // default fallback

		// params
		$this->assertEquals(7, $p->g);
		$this->assertEquals(8, $p->get('h', 'xx')); // default will not be efective

		// server works
		$this->assertEquals(5, $p->_SERVER()->e);
		$this->assertEquals(6, $p->_SERVER()->f);
	}

	public function testMutatingParamsWorks()
	{
		$p = new Params();

		$p->_GET()->g = 1;
		$p->_POST()->p = 2;
		$p->x = 3;
		$p->set('y', 4);
		$p->z = 5;
		$p->_SERVER()->s = 6;

		$this->assertEquals(1, $p->g);
		$this->assertEquals(2, $p->p);
		$this->assertEquals(3, $p->x);
		$this->assertEquals(4, $p->y);
		$this->assertEquals(5, $p->z);
		$this->assertEquals(null, $p->s);
		$this->assertEquals(6, $p->_SERVER()->s);

		$a = ['x' => 3, 'y' => 4, 'z' => 5, 'g' => 1, 'p' => 2];
		$this->assertEquals($a, $p->get());
	}

	public function testPriorityIsFromCustomToGetToPost()
	{
		$p = new Params([
			'_GET' => ['a' => 'g'],
			'_POST' => ['a' => 'p'],
			'_SERVER' => ['a' => 's'],
			'params' => ['a' => 'x'],
		]);

		$this->assertEquals('x', $p['a']); // params have higher priority
		$this->assertEquals('s', $p->_SERVER()->a); // server is not merged

		$this->assertEquals(['a' => 'x'], $p->toArray());
	}

	public function testCommonArrayIsConsideredParamsAtConstructor()
	{
		$p = new Params([
			'a' => 1,
			'b' => 2,
		]);
		$this->assertEquals(1, $p['a']);
		$this->assertEquals(2, $p['b']);
	}

	public function testParamsCookieProxiesToCookieSuperglobal()
	{
		$p = new Params();
		$this->assertNull($p->test);
		$this->assertNull($p->_COOKIE()->test);

		$_COOKIE['test'] = 1;

		$p = new Params();
		$this->assertNull($p->test);
		$this->assertEquals(1, $p->_COOKIE()->test);
	}

	public function testCookieParamCanBeMocked()
	{
		$p = new Params([
			'_COOKIE' => ['a' => 'b'],
		]);

		// cookie sa defaultne nemerguje
		$this->assertNull($p->a);

		// ale takto je normalne viditelna
		$this->assertEquals('b', $p->_COOKIE()->a);

		// a povodny kontajner je vo vyssom pripade nedotknuty
		$this->assertEmpty($_COOKIE);
	}

	public function testResetWorks()
	{
		$p = new Params([
			'_GET' => ['a' => 'g'],
			'_POST' => ['a' => 'p'],
			'_SERVER' => ['a' => 's'],
			'params' => ['a' => 'x'],
		]);

		$p->reset();

		$this->assertNull($p->a);
	}

	public function testIssetAndEmptyOnMagicFieldsWorks()
	{
		$p = new Params([
			'_GET' => ['a' => 'g'],
			'_POST' => ['a' => 'p'],
			'_SERVER' => ['a' => 's'],
			'params' => ['a' => 'x', 'b' => ''],
		]);

		// vsetky definovane fieldy
		$this->assertTrue(isset($p->a));
		$this->assertTrue(isset($p->b));

		// akekolvek nedefinovane nesmu byt isset
		$this->assertFalse(isset($p->foo));

		// "empty" na definovane musi odpovedat realite
		$this->assertFalse(empty($p->a)); // jediny defaultne neempty
		$this->assertTrue(empty($p->b));

		// akolvek nedefinovane musia byt empty
		$this->assertTrue(empty($p->foo));
		$this->assertTrue(empty($p->bar));
	}

	public function testIssetAndEmptyOnMagicFieldsWorks2()
	{
		$p = new Params([
			'_GET' => ['a' => 'g', 'b' => 'b'],
			'params' => ['a' => 'x', 'b' => null], // vyssia priorita
		]);

		// vsetky definovane fieldy
		$this->assertTrue(isset($p->a));
		$this->assertFalse(isset($p->b));

		// "empty" na definovane musi odpovedat realite
		$this->assertFalse(empty($p->a));
		$this->assertTrue(empty($p->b));

		//
		unset($p->a);
		$this->assertFalse(isset($p->a));
		$this->assertTrue(empty($p->a));
		$this->assertNull($p->a);
	}
}
