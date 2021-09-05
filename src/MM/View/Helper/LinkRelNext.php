<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class LinkRelNext extends LinkRelUnique {
	protected ?string $_rel = 'next';
}
