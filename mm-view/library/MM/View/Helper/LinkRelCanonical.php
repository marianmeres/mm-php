<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

use MM\Util\Url;
use MM\View\Helper;
use MM\View\Exception;

class LinkRelCanonical extends LinkRelUnique
{
    protected $_rel = 'canonical';

    /**
     * @param $href
     * @return $this
     */
    public function setHref($href)
    {
        if (null != $href) {
            $href = Canonicalize::url($href);
        }
        return parent::setHref($href);
    }

}
