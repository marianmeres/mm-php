<?php
namespace MM\Controller\Exception;
use MM\Controller\Exception;
class Forbidden extends Exception
{
	protected $code = 403;
	protected $message = 'Forbidden';
}
