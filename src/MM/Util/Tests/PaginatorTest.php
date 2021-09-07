<?php declare(strict_types=1);

namespace MM\Util\Tests;

use MM\Util\Paginator;
use PHPUnit\Framework\TestCase;

/**
 * @group mm-util
 */
class PaginatorTest extends TestCase {
	public function testPaginatorWorks() {
		$p = new Paginator(101, 10, 1);

		$this->assertEquals(10, $p->getLimit());

		$this->assertCount(11, $p);
		$this->assertEquals(11, $p->getPageCount());

		// OFFSET says to skip that many rows before beginning to return rows.
		$this->assertEquals(0, $p->getOffsetByPage(1));
		$this->assertEquals(10, $p->getOffsetByPage(2));
		$this->assertEquals(100, $p->getOffsetByPage(11));

		//
		$this->assertEquals(1, $p->getPageByOffset(0));
		$this->assertEquals(1, $p->getPageByOffset(9));
		$this->assertEquals(2, $p->getPageByOffset(10));
		$this->assertEquals(2, $p->getPageByOffset(11));
		$this->assertEquals(11, $p->getPageByOffset(100));

		//
		$this->assertFalse($p->isOutOfBounds(0));
		$this->assertFalse($p->isOutOfBounds(1));
		$this->assertFalse($p->isOutOfBounds(11));
		$this->assertTrue($p->isOutOfBounds(12));

		// next page id
		$this->assertEquals(2, $p->getNextPage());
		$this->assertEquals(11, $p->getNextPage(10));
		$this->assertFalse($p->getNextPage(11)); // out of bounds

		// previous page id
		$this->assertFalse($p->getPreviousPage()); // mensia ako 0 neexistuje
		$this->assertEquals(10, $p->getPreviousPage(11));
		$this->assertEquals(10, $p->getPreviousPage(123));

		// last
		$this->assertFalse($p->isLastPage());
		$this->assertFalse($p->isLastPage(1));
		$this->assertFalse($p->isLastPage(5));
		$this->assertTrue($p->isLastPage(11));

		// first
		$this->assertTrue($p->isFirstPage());
		$this->assertTrue($p->isFirstPage(1));
		$this->assertFalse($p->isFirstPage(5));
		$this->assertFalse($p->isFirstPage(11));
		$this->assertFalse($p->isFirstPage(1102));

		//prx($p->dump());
	}
}
