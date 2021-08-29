<?php
declare(strict_types=1);

namespace MM\Controller\Exception;
use MM\Controller\Exception;

/**
 * This is ment to be used only under special cases, where forced redirect is
 * needed - so it can be easily tracked (e.g. force https when using credit cards...)
 * Message might contain the actual url to be redirected to.
 */
class Redirect extends Exception {
	protected $code = 301;
	protected $message = 'Redirect';
}
