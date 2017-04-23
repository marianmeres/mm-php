<?php
namespace MM\Util;

/**
 * Nothing fancy, just basic calculations.
 */
class Paginator implements \Countable
{
    /**
     * @var int
     */
    protected $_itemsTotal;

    /**
     * @var int
     */
    protected $_itemsPerPage;

    /**
     * @var int
     */
    protected $_currentPageId;

    /**
     * @param $total
     * @param int $perPage
     * @param int $currentPage
     */
    public function __construct($total, $perPage = 10, $currentPage = 1)
    {
        $this->setItemsTotal($total);
        $this->setItemsPerPage($perPage);
        $this->setCurrentPageId($currentPage);
    }

    /**
     * @param int $total
     * @return $this
     */
    public function setItemsTotal($total)
    {
        $this->_itemsTotal = (int) $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemsTotal()
    {
        return $this->_itemsTotal;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function setItemsPerPage($count)
    {
        $this->_itemsPerPage = max(1, (int) $count);
        return $this;
    }

    /**
     * @return int
     */
    public function getItemsPerPage()
    {
        return $this->_itemsPerPage;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setCurrentPageId($id)
    {
        $this->_currentPageId = max(1, (int) $id);
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPageId()
    {
        return $this->_currentPageId;
    }

    /**
     * @return int float
     */
    public function getPageCount()
    {
        return ceil($this->_itemsTotal / $this->_itemsPerPage);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->getPageCount();
    }

    /**
     * Returns interval from (exclusive) - to (inclusive); Offset is considered
     * as postgres does: OFFSET says to skip that many rows before beginning to return rows.
     *
     * @param null $pageId
     * @param bool|true $noInterval
     * @return array|int
     */
    public function getOffsetByPageId($pageId = null, $noInterval = true)
    {
        $pageId = (null == $pageId) ? $this->_currentPageId : (int) $pageId;

        $out = array(
            max(($this->_itemsPerPage * ($pageId - 1)), 0),
            min($this->_itemsTotal, $this->_itemsPerPage * $pageId)
        );
        if ($noInterval) {
            return $out[0];
        }
        return $out;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->getOffsetByPageId(null);
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->_itemsPerPage;
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function getPageIdByOffset($offset)
    {
        $offset = (int) $offset;

        // moze byt aj zaporny, vtedy odratavam s total items
        if ($offset < 0) {
            $offset = max(0, $this->_itemsTotal + $offset);
        }

        // OFFSET says to skip that many rows before beginning to return rows.
        $offset++;

        return max(ceil($offset / $this->_itemsPerPage), 1);
    }

    /**
     * @param null $page
     * @return bool
     */
    public function isOutOfBounds($page = null)
    {
        if (null === $page) {
            $page = $this->_currentPageId;
        }

        // lebo page 0 neexistuje, tak max(..., 1)
        return max($this->getPageCount(), 1) < (int) $page;
    }

    /**
     * @param null $page
     * @return bool|int
     */
    public function getNextPageId($page = null)
    {
        if (null === $page) {
            $page = $this->_currentPageId;
        }
        $page = (int) $page;

        $n = $page + 1;
        return $this->getPageCount() >= $n ? $n : false;
    }

    /**
     * @param null $page
     * @return bool|int
     */
    public function getPreviousPageId($page = null)
    {
        if (null === $page) {
            $page = $this->_currentPageId;
        }
        $page = (int) $page;

        $p = (int) max(0, min($page - 1, $this->getPageCount() - 1));
        return $p != 0 ? $p : false;
    }

    /**
     * @param null $page
     * @return bool
     */
    public function isLastPageId($page = null)
    {
        if (null === $page) {
            $page = $this->_currentPageId;
        }
        $page = (int) $page;

        return ($page == $this->getPageCount());
    }

    /**
     * @param null $page
     * @return bool
     */
    function isFirstPageId($page = null)
    {
        if (null === $page) {
            $page = $this->_currentPageId;
        }
        $page = (int) $page;

        return ($page == 1);
    }

    /**
     * @return array
     */
    public function dump()
    {
        return array(
            'itemsTotal'     => $this->getItemsTotal(),
            'itemsPerPage'   => $this->getItemsPerPage(),
            'currentPageId'  => $this->getCurrentPageId(),
            'pageCount'      => $this->getPageCount(),
            'offsetByPageId' => $this->getOffsetByPageId(),
            'isOutOfBounds'  => $this->isOutOfBounds(),
            'nextPageId'     => $this->getNextPageId(),
            'previousPageId' => $this->getPreviousPageId(),
            'isLastPageId'   => $this->isLastPageId(),
            'isFirstPageId'  => $this->isFirstPageId(),
        );
    }
}
