<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\Util\Url;
use MM\View\Helper;
use MM\View\Exception;

class LinkRelCanonical extends LinkRelUnique {
	protected string $_rel = 'canonical';

	public function setHref($href): static {
		if (null != $href) {
			$href = Canonicalize::url($href);
		}
		return parent::setHref($href);
	}
}
