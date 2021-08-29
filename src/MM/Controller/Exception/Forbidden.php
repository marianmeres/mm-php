<?php
declare(strict_types=1);

namespace MM\Controller\Exception;
use MM\Controller\Exception;
class Forbidden extends Exception {
	protected $code = 403;
	protected $message = 'Forbidden';
}
