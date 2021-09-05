<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class LinkRelPrev extends LinkRelUnique {
	protected ?string $_rel = 'prev';
}
