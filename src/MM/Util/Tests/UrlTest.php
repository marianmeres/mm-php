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
		];
		foreach ($urls as $url) {
			$this->assertEquals($url, Url::build(Url::parse($url)));
		}
	}

	public function testParseQueryWorks() {
		$p = Url::parse('http://username:password@hostname/path?arg=value&foo=bar#anchor');
		$this->assertEquals('value', $p['query']['arg']);
		$this->assertEquals('bar', $p['query']['foo']);
	}
}
