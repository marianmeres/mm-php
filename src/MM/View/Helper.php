<?php
namespace MM\View;

class Helper {
	protected ?View $_view;

	public function __construct(ViewAbstract $view = null) {
		if ($view) {
			$this->setView($view);
			$this->_init();
		}
	}

	public static function factory(ViewAbstract $view = null) {
		return new static($view);
	}

	public function setView(ViewAbstract $view = null): static {
		$this->_view = $view;
		return $this;
	}

	/**
	 * init hook
	 */
	protected function _init() {
	}
}
