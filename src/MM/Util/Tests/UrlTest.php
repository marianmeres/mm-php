<?php declare(strict_types=1);

namespace MM\Util\Tests;

use MM\Util\Url;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-util
 */
final class UrlTest extends TestCase {
	public function testParseAndBuildWorks() {
		$urls = [
			'http://username:password@hostname/path?arg=value#anchor',
			'https://example.com/?a=some%2Fnew&foo=bar',
			'http://abc.some.com:8080/', // musi koncit s /
			'http://abc.some.com:8080/#some',
			'http://abccom:8080/?a=some%2Fnew',
			'http://abccom:8080/lon/gpa/th/',
			'/path/only',
			'/path/only?foo=bar',
			'/path/only?foo=bar#baz',
		];
		foreach ($urls as $url) {
			$this->assertEquals($url, Url::build(Url::parse($url)));
		}
	}

	public function testWithQueryWorks() {
		$this->assertEquals(
			'/foo?a=1',
			Url::withQueryVars('/foo', ['a' => 1, 'b' => null]),
		);
		$this->assertEquals('/foo?a=2', Url::withQueryVars('/foo?a=1', ['a' => 2]));
		$this->assertEquals('/foo', Url::withQueryVars('/foo?a=1', ['a' => null]));
	}

	public function testParseQueryWorks() {
		$p = Url::parse(
			'http://username:password@hostname/path?arg=value&foo=bar#anchor',
		);
		$this->assertEquals('value', $p['query']['arg']);
		$this->assertEquals('bar', $p['query']['foo']);
	}

	public function testWithQueryVarsWorks() {
		$actual = Url::withQueryVars(
			'http://username:password@hostname/path?arg=value&foo=bar#anchor',
			[
				'x' => 'y',
				'arg' => null,
				'foo' => 'baz',
				'baz' => 'bat',
			],
		);

		// query is modified and sorted
		$expected = 'http://username:password@hostname/path?baz=bat&foo=baz&x=y#anchor';

		$this->assertEquals($expected, $actual);
	}
}
