<?php declare(strict_types=1);

namespace MM\Util;

/**
 * Nothing fancy, just basic calculations.
 */
class Paginator implements \Countable {
	protected int $_itemsTotal;

	protected int $_itemsPerPage;

	protected int $_currentPageId;

	public function __construct($total, int $perPage = 10, int $currentPage = 1) {
		$this->setItemsTotal($total);
		$this->setItemsPerPage($perPage);
		$this->setCurrentPageId($currentPage);
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

	public function setCurrentPageId(int $id): static {
		$this->_currentPageId = max(1, $id);
		return $this;
	}

	public function getCurrentPageId(): int {
		return $this->_currentPageId;
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
	public function getOffsetByPageId($pageId = null, bool $noInterval = true): int|array {
		$pageId = null === $pageId ? $this->_currentPageId : (int) $pageId;

		$out = [
			max($this->_itemsPerPage * ($pageId - 1), 0),
			min($this->_itemsTotal, $this->_itemsPerPage * $pageId),
		];
		if ($noInterval) {
			return $out[0];
		}
		return $out;
	}

	public function getOffset(): int {
		return $this->getOffsetByPageId(null);
	}

	public function getLimit(): int {
		return $this->_itemsPerPage;
	}

	public function getPageIdByOffset(int $offset): int {
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
			$page = $this->_currentPageId;
		}

		// lebo page 0 neexistuje, tak max(..., 1)
		return max($this->getPageCount(), 1) < (int) $page;
	}

	public function getNextPageId(?int $page = null): bool|int {
		if (null === $page) {
			$page = $this->_currentPageId;
		}
		// $page = (int) $page;

		$n = $page + 1;
		return $this->getPageCount() >= $n ? $n : false;
	}

	public function getPreviousPageId(?int $page = null): bool|int {
		if (null === $page) {
			$page = $this->_currentPageId;
		}
		// $page = (int) $page;

		$p = (int) max(0, min($page - 1, $this->getPageCount() - 1));
		return $p != 0 ? $p : false;
	}

	public function isLastPageId(?int $page = null): bool {
		if (null === $page) {
			$page = $this->_currentPageId;
		}
		// $page = (int) $page;

		return $page === $this->getPageCount();
	}

	function isFirstPageId(?int $page = null): bool {
		if (null === $page) {
			$page = $this->_currentPageId;
		}
		// $page = (int) $page;

		return $page === 1;
	}

	public function dump(): array {
		return [
			'itemsTotal' => $this->getItemsTotal(),
			'itemsPerPage' => $this->getItemsPerPage(),
			'currentPageId' => $this->getCurrentPageId(),
			'pageCount' => $this->getPageCount(),
			'offsetByPageId' => $this->getOffsetByPageId(),
			'isOutOfBounds' => $this->isOutOfBounds(),
			'nextPageId' => $this->getNextPageId(),
			'previousPageId' => $this->getPreviousPageId(),
			'isLastPageId' => $this->isLastPageId(),
			'isFirstPageId' => $this->isFirstPageId(),
		];
	}
}
