<?php
namespace MM\View\Tests;

use MM\View\View;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-view
 */
final class ViewTest extends TestCase
{
	public function testViewVarsCanBeSetAndRead()
	{
		$v = new View();
		$vars = $v->dump();
		$this->assertTrue(empty($vars));
		$v->a = 1;
		$this->assertEquals(1, $v->a);
		$vars = $v->dump();
		$this->assertFalse(empty($vars));
	}

	public function testAccessingUndefinedVarTriggersNotice()
	{
		$this->expectNotice();
		$v = new View();
		$v->some;
	}

	public function testValuesAreEscapedByDefault()
	{
		$v = new View([
			'vars' => [
				'a' => '>',
			],
		]);
		$this->assertEquals('&gt;', $v->a);
	}

	public function testRawValueIsAccessibleViaRawMethod()
	{
		$v = new View();
		$v->a = '>';
		$this->assertEquals('>', $v->raw('a'));
	}

	public function testBasicRenderWorks()
	{
		$v = new View();
		$tpl = __DIR__ . '/_tpl/123.phtml';
		$this->assertEquals('123', $v->render($tpl));
	}

	public function testBasicRenderWorks2()
	{
		$v = new View();
		$v->a = 123;
		$tpl = __DIR__ . '/_tpl/a.phtml';
		$this->assertEquals('123', $v->render($tpl));
	}

	public function testThisWithinTemplateRefersToViewObjectAndOnlyPublicScopeIsAccessible()
	{
		$v = new View();
		$tpl = __DIR__ . '/_tpl/this.phtml';
		$this->assertEquals('1', $v->render($tpl));
	}

	public function testNonScalarTypesAreNotEscaped()
	{
		$v = new View();
		$v->a = new \stdClass();
		$v->b = [];

		// toto musi vratit normalne povodne veci
		$this->assertTrue($v->a instanceof \stdClass);
		$this->assertEquals([], $v->b);
	}

	public function testHelperIntegration()
	{
		$v = new View(['vars' => ['foo' => 'bar']]);
		$tpl = __DIR__ . '/_tpl/with-helper.phtml';
		$this->assertEquals('bar', $v->render($tpl));
	}
}
