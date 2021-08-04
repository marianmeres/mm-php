<?php declare(strict_types=1);

namespace MM\Util\Tests;

use MM\Util\Str;
use MM\Util\Url;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-util
 */
final class StrTest extends TestCase {
	public function testEndsWithWorks() {
		$this->assertTrue(Str::endsWith('foo', 'o'));
		$this->assertTrue(Str::endsWith('foo', 'oo'));
		$this->assertFalse(Str::endsWith('foo', 'O'));
		$this->assertFalse(Str::endsWith('foo', 'x'));
	}

	public function testStartsWithWorks() {
		$this->assertTrue(Str::startsWith('foo', 'f'));
		$this->assertTrue(Str::startsWith('foo', 'fo'));
		$this->assertFalse(Str::startsWith('foo', 'F'));
		$this->assertFalse(Str::startsWith('foo', 'x'));
	}
}
