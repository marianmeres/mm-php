<?php
namespace MM\View;

class Helper {
	/**
	 * @var View
	 */
	protected $_view;

	/**
	 * @param ViewAbstract $view
	 */
	public function __construct(ViewAbstract $view = null) {
		if ($view) {
			$this->setView($view);
			$this->_init();
		}
	}

	public static function factory(ViewAbstract $view = null) {
		return new static($view);
	}

	/**
	 * @param ViewAbstract $view
	 * @return $this
	 */
	public function setView(ViewAbstract $view = null) {
		$this->_view = $view;
		return $this;
	}

	/**
	 * init hook
	 */
	protected function _init() {
	}
}
