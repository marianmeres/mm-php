<?php
declare(strict_types=1);

namespace MM\Controller\Exception;
use MM\Controller\Exception;
class Unauthorized extends Exception {
	protected $code = 401;
	protected $message = 'Unauthorized';
}
