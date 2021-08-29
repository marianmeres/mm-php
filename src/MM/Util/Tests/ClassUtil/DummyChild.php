<?php
declare(strict_types=1);

namespace MM\Util\Tests\ClassUtil;

class DummyChild extends DummyParent {
	public function __construct() {
		throw new \Exception('DummyChild::__construct intentional ex');
	}

	/**
	 * Umyslne overridneme so zlou signaturou
	 */
	public function foo(array $x, array $y) {
	}
}
