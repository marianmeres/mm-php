<?php declare(strict_types=1);

namespace MM\Util\Tests;

use MM\Util\Hms;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/_bootstrap.php';

/**
 * @group mm-util
 */
final class HmsTest extends TestCase
{
	public function testHmsWorks()
	{
		$this->assertEquals('00:00:10', Hms::get(10));
		$this->assertEquals('00:01:00', Hms::get(60));
		$this->assertEquals('00:02:01', Hms::get(121));
		$this->assertEquals('00:59:59', Hms::get(3599));
		$this->assertEquals('01:00:00', Hms::get(3600));
		$this->assertEquals('01:01:01', Hms::get(3661));
		$this->assertEquals('01:59:59', Hms::get(7199));
	}
}
