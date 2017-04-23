<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

abstract class LinkRelUnique extends Helper
{
    protected $_rel;

    /**
     * @var string
     */
    protected $_href;

    /**
     * @param null $href
     * @return $this
     */
    public function __invoke($href = null)
    {
        $href && $this->setHref($href);
        return $this;
    }

    /**
     * @param $href
     * @return $this
     */
    public function setHref($href)
    {
        $this->_href = $href;
        return $this;
    }

    /**
     * @return string
     */
    public function getHref()
    {
        return $this->_href;
    }

    /**
     * @return string
     */
    public function toString()
    {
        $out = '';

        if ($this->_href !== null) {
            $out = "<link rel='$this->_rel' href='$this->_href'/>\n";
        }

        return $out;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
}
