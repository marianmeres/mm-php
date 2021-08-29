<?php

namespace MM\View\Helper;

class HeadScriptSrc extends ContainerOfStrings {
	/**
	 * @var bool
	 */
	protected $_unique = true;

	/**
	 * @var bool
	 */
	protected $_escape = false;

	/**
	 * @return string
	 */
	public function toString() {
		$out = '';
		foreach ($this->_container as $src) {
			$out .= "<script src='$src'></script>\n";
		}
		return $out;
	}
}
