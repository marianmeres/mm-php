<?php

namespace MM\Util\Tests\ClassUtil;

class DummyChild extends DummyParent
{
	public function __construct()
	{
		throw new \Exception('DummyChild::__construct intentional ex');
	}

	/**
	 * Umyslne overridneme so zlou signaturou
	 */
	public function foo($wrong)
	{
	}
}
