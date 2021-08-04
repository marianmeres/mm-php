<?php
/**
 * @author Marian Meres
 */
namespace MM\Controller;

/**
 * Class Helper
 * @package MM\Controller
 */
abstract class Helper {
	/**
	 * @var AbstractController
	 */
	protected $_controller;

	/**
	 * @param AbstractController $controller
	 */
	public function __construct(AbstractController $controller = null) {
		if ($controller) {
			$this->setController($controller);
			$this->_init();
		}
	}

	/**
	 * @param AbstractController $controller
	 * @return $this
	 */
	public function setController(AbstractController $controller = null) {
		$this->_controller = $controller;
		return $this;
	}

	/**
	 * init hook
	 */
	protected function _init() {
	}
}
