<?php
namespace MM\Util\Tests;

use MM\Util\Paginator;
use PHPUnit\Framework\TestCase;

/**
 * @group mm-util
 */
class PaginatorTest extends TestCase
{
	public function testPaginatorWorks()
	{
		$p = new Paginator(101, 10, 1);

		$this->assertEquals(10, $p->getLimit());

		$this->assertEquals(11, count($p));
		$this->assertEquals(11, $p->getPageCount());

		// OFFSET says to skip that many rows before beginning to return rows.
		$this->assertEquals(0, $p->getOffsetByPageId(1));
		$this->assertEquals(10, $p->getOffsetByPageId(2));
		$this->assertEquals(100, $p->getOffsetByPageId(11));

		//
		$this->assertEquals(1, $p->getPageIdByOffset(0));
		$this->assertEquals(1, $p->getPageIdByOffset(9));
		$this->assertEquals(2, $p->getPageIdByOffset(10));
		$this->assertEquals(2, $p->getPageIdByOffset(11));
		$this->assertEquals(11, $p->getPageIdByOffset(100));

		//
		$this->assertFalse($p->isOutOfBounds(0));
		$this->assertFalse($p->isOutOfBounds(1));
		$this->assertFalse($p->isOutOfBounds(11));
		$this->assertTrue($p->isOutOfBounds(12));

		// next page id
		$this->assertEquals(2, $p->getNextPageId());
		$this->assertEquals(11, $p->getNextPageId(10));
		$this->assertFalse($p->getNextPageId(11)); // out of bounds

		// previous page id
		$this->assertFalse($p->getPreviousPageId()); // mensia ako 0 neexistuje
		$this->assertEquals(10, $p->getPreviousPageId(11));
		$this->assertEquals(10, $p->getPreviousPageId(123));

		// last
		$this->assertFalse($p->isLastPageId());
		$this->assertFalse($p->isLastPageId(1));
		$this->assertFalse($p->isLastPageId(5));
		$this->assertTrue($p->isLastPageId(11));

		// first
		$this->assertTrue($p->isFirstPageId());
		$this->assertTrue($p->isFirstPageId(1));
		$this->assertFalse($p->isFirstPageId(5));
		$this->assertFalse($p->isFirstPageId(11));
		$this->assertFalse($p->isFirstPageId(1102));

		//prx($p->dump());
	}
}
