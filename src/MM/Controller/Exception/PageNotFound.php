<?php
namespace MM\Controller\Exception;
use MM\Controller\Exception;
class PageNotFound extends Exception {
	const CODE = 404;
	protected $code = self::CODE;
	protected $message = 'Page Not Found';
}
