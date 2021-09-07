<?php declare(strict_types=1);

namespace MM\Util;

/**
 * Nothing fancy, just basic calculations.
 */
class Paginator implements \Countable {
	protected int $_itemsTotal;

	protected int $_itemsPerPage;

	protected int $_currentPage;

	public function __construct($total, int $perPage = 10, int $currentPage = 1) {
		$this->setItemsTotal($total);
		$this->setItemsPerPage($perPage);
		$this->setCurrentPage($currentPage);
	}

	public function setItemsTotal(int $total): static {
		$this->_itemsTotal = (int) $total;
		return $this;
	}

	public function getItemsTotal(): int {
		return $this->_itemsTotal;
	}

	public function setItemsPerPage(int $count): static	{
		$this->_itemsPerPage = max(1, (int) $count);
		return $this;
	}

	public function getItemsPerPage(): int {
		return $this->_itemsPerPage;
	}

	public function setCurrentPage(int $id): static {
		$this->_currentPage = max(1, $id);
		return $this;
	}

	public function getCurrentPage(): int {
		return $this->_currentPage;
	}

	public function getPageCount(): int {
		return (int) ceil($this->_itemsTotal / $this->_itemsPerPage);
	}

	public function count(): int {
		return $this->getPageCount();
	}

	/**
	 * Returns interval from (exclusive) - to (inclusive); Offset is considered
	 * as postgres does: OFFSET says to skip that many rows before beginning to return rows.
	 */
	public function getOffsetByPage($page = null, bool $noInterval = true): int|array {
		$page = null === $page ? $this->_currentPage : (int) $page;

		$out = [
			max($this->_itemsPerPage * ($page - 1), 0),
			min($this->_itemsTotal, $this->_itemsPerPage * $page),
		];
		if ($noInterval) {
			return $out[0];
		}
		return $out;
	}

	public function getOffset(): int {
		return $this->getOffsetByPage(null);
	}

	public function getLimit(): int {
		return $this->_itemsPerPage;
	}

	public function getPageByOffset(int $offset): int {
		// moze byt aj zaporny, vtedy odratavam s total items
		if ($offset < 0) {
			$offset = max(0, $this->_itemsTotal + $offset);
		}

		// OFFSET says to skip that many rows before beginning to return rows.
		$offset++;

		return (int) max(ceil($offset / $this->_itemsPerPage), 1);
	}

	public function isOutOfBounds($page = null): bool {
		if (null === $page) {
			$page = $this->_currentPage;
		}

		// lebo page 0 neexistuje, tak max(..., 1)
		return max($this->getPageCount(), 1) < (int) $page;
	}

	public function getNextPage(?int $page = null): bool|int {
		if (null === $page) {
			$page = $this->_currentPage;
		}
		// $page = (int) $page;

		$n = $page + 1;
		return $this->getPageCount() >= $n ? $n : false;
	}

	public function getPreviousPage(?int $page = null): bool|int {
		if (null === $page) {
			$page = $this->_currentPage;
		}
		// $page = (int) $page;

		$p = (int) max(0, min($page - 1, $this->getPageCount() - 1));
		return $p != 0 ? $p : false;
	}

	public function isLastPage(?int $page = null): bool {
		if (null === $page) {
			$page = $this->_currentPage;
		}
		// $page = (int) $page;

		return $page === $this->getPageCount();
	}

	function isFirstPage(?int $page = null): bool {
		if (null === $page) {
			$page = $this->_currentPage;
		}
		// $page = (int) $page;

		return $page === 1;
	}

	public function dump(): array {
		return [
			'itemsTotal' => $this->getItemsTotal(),
			'itemsPerPage' => $this->getItemsPerPage(),
			'currentPage' => $this->getCurrentPage(),
			'pageCount' => $this->getPageCount(),
			'offsetByPage' => $this->getOffsetByPage(),
			'isOutOfBounds' => $this->isOutOfBounds(),
			'nextPage' => $this->getNextPage(),
			'previousPage' => $this->getPreviousPage(),
			'isLastPage' => $this->isLastPage(),
			'isFirstPage' => $this->isFirstPage(),
		];
	}
}
