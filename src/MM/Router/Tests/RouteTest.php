<?php declare(strict_types=1);

namespace MM\Router\Tests;

use MM\Router\Route;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-router
 */
final class RouteTest extends TestCase {
	public function testParseWorks() {
		// prettier-ignore
		$data = [
			// no-match
			['/foo', 					 '/bar', 			  null],
			['/foo',                     '',                  null],
			['/foo/[bar]',               '/foo',              null],
			['/[foo]/bar',               '/foo',              null],
			['/[foo]/[bar]',             '/foo',              null],
			// match no params (trailing separators are OK)
			['/',                        '',                  []],
			['',                         '///',               []],
			['foo',                      'foo',               []],
			['//foo///bar.baz/',         'foo/bar.baz',       []],
			['foo/bar/baz',              '//foo//bar/baz//',  []],
			['#/foo//',                  '#/foo',             []],
			// match with params
			['/[foo]',                   '/bar',              [ 'foo' => 'bar' ]],
			['#/foo/[bar]',              '#/foo/bat',         [ 'bar' => 'bat' ]],
			['#/[foo]/[bar]',            '#/baz/bat',         [ 'foo' => 'baz', 'bar' => 'bat' ]],
			['#/[foo]/bar',              '#/baz/bar',         [ 'foo' => 'baz' ]],
			// params with regex constraint
			['/[id([0-9]+)]',            '/123',              [ 'id' => '123' ]],
			['/[id(\\d-\\d)]',           '/1-2',              [ 'id' => '1-2' ]],
			['/[id([0-9]+)]',            '/foo',              null],
			['/foo/[bar]/[id([0-9]+)]',  '/foo/baz/123',      [ 'bar' => 'baz', 'id' => '123' ]],
			['/foo/[id([0-9]+)]/[bar]',  '/foo/bar/baz',      null],
			// wrong regex - missing name...
			['/foo/[([0-9]+)]',          '/foo/123',          null],
			//
			['/[foo(bar)]/[baz]',         '/bar/bat',         [ 'foo' => 'bar', 'baz' => 'bat' ]],
			['/[foo(bar)]/[baz]',         '/baz/bat',         null],
			// url encoded segments and values
			['/foo/[id%20x]',             '/foo/12%203',      [ 'id x' => '12 3' ]],
			// optional param
			['/foo?',                     '/',                []],
			['/foo?',                     '/foo',             []],
			['/foo?',                     '/bar',             null],
			['/foo/[bar]?',               '/foo',             []],
			['/foo/[bar]?',               '/foo/bar',         [ 'bar' => 'bar' ]],
			['/foo/[bar]?',               '/foo/bar/baz',     null],
			['/foo/[bar([0-9]+)]?',       '/foo',             []],
			['/foo/[bar([0-9]+)]?',       '/foo/bar',         null],
			['/foo/[bar([0-9]+)]?',       '/foo/123',         [ 'bar' => '123' ]],
			['/foo/[bar([0-9]+)]?',       '/foo/123/baz',     null],
			//
			['/foo/[bar]?/baz',           '/foo',             null],
			['/foo/[bar]?/baz',           '/foo/bar',         null], // !!! must not match
			['/foo/[bar]?/baz',           '/foo/baz',         null], // !!! must not match
			['/foo/[bar]?/baz',           '/foo/bar/baz',     [ 'bar' => 'bar' ]],
			['/foo/[bar]?/[baz]?',        '/foo',             []],
			['/foo/[bar]?/[baz]?',        '/foo/bar',         [ 'bar' => 'bar' ]],
			['/foo/[bar]?/[baz]?',        '/foo/bar/baz',     [ 'bar' => 'bar', 'baz' => 'baz' ]],
			// spread params
			['/js/[...path]',             '/js/foo/bar/baz.js', [ 'path' => 'foo/bar/baz.js' ]],
			['/js/[root]/[...path]',      '/js/foo/bar/baz.js', [ 'root' => 'foo', 'path' => 'bar/baz.js' ]],
			['/js/[...path]/[file]',      '/js/foo/bar/baz.js', [ 'path' => 'foo/bar', 'file' => 'baz.js' ]],
			['/[...path]/[file]',         '/foo/bar/baz.js',    [ 'path' => 'foo/bar', 'file' => 'baz.js' ]],
		];

		foreach ($data as $row) {
			$route = $row[0];
			$input = $row[1];
			$expected = $row[2];

			$actual = Route::factory($route)->parse($input);
			$this->assertEquals(
				$expected,
				$actual,
				"$route -> $input => " . json_encode($expected),
			);
		}
	}

	public function testQueryParamsParsingWorksAndIsEnabledByDefault() {
		$actual = Route::factory('/foo/[bar]')->parse('/foo/bar?baz=ba%20t');
		$this->assertEquals(
			['baz' => 'ba t', 'bar' => 'bar'],
			$actual,
			json_encode($actual),
		);

		// no match must still be no match
		$actual = Route::factory('/foo/[bar]')->parse('/hoho?bar=bat');
		$this->assertEquals(null, $actual, json_encode($actual));
	}

	public function testPathParamsHavePriorityOverQueryParams() {
		$actual = Route::factory('/foo/[bar]')->parse('/foo/bar?bar=bat');
		$this->assertEquals(['bar' => 'bar'], $actual, json_encode($actual));
	}

	public function testQueryParamsParsingCanBeDisabled() {
		$actual = Route::factory('/foo/[bar]')->parse('/foo/bar?baz=bat', false);
		$this->assertEquals(['bar' => 'bar?baz=bat'], $actual, json_encode($actual));
		// note added slash
		$actual = Route::factory('/foo/[bar]')->parse('/foo/bar/?baz=bat', false);
		$this->assertEquals(null, $actual, json_encode($actual));
	}

	public function testSpreadParamMustNotBeOptional() {
		$this->expectException(\Error::class);
		Route::factory('[...path]?');
	}

	public function testThereCanBeOnlyOneSpreadSegment() {
		$this->expectException(\Error::class);
		Route::factory('/foo/[...some]/bar/[...another]');
	}

	// public function testFoo() {
	// 	$actual = Route::factory('/js/[...path]/[file]')->parse('/js/foo/bar/baz.js');
	// 	prx($actual);
	// }
}
