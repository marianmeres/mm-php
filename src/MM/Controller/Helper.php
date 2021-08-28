<?php
namespace MM\Controller;

/**
 * Class Helper
 * @package MM\Controller
 */
abstract class Helper {
	protected ?AbstractController $_controller = null;

	public function __construct(AbstractController $controller = null) {
		if ($controller) {
			$this->setController($controller);
			$this->_init();
		}
	}

	public function setController(AbstractController $controller = null): Helper {
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * init hook
	 */
	protected function _init() {
	}
}
