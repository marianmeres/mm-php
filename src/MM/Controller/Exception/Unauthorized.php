<?php
namespace MM\Controller\Exception;
use MM\Controller\Exception;
class Unauthorized extends Exception {
	protected $code = 401;
	protected $message = 'Unauthorized';
}
