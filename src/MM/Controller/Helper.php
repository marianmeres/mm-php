<?php
declare(strict_types=1);

namespace MM\Controller;

abstract class Helper {
	protected ?AbstractController $_controller = null;

	public function __construct(AbstractController $controller = null) {
		if ($controller) {
			$this->setController($controller);
			$this->_init();
		}
	}

	public function setController(AbstractController $controller = null): static {
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * init hook
	 */
	protected function _init() {
	}
}
