<?php

namespace MM\View\Helper;

class HeadScript extends ContainerOfStrings {
	/**
	 * @var bool
	 */
	protected bool $_unique = false;

	/**
	 * @var bool
	 */
	protected bool $_doEscape = false;

	/**
	 * @return string
	 */
	public function toString() {
		$out = '';
		foreach ($this->_container as $string) {
			$out .= "<script>\n$string\n</script>\n";
		}
		return $out;
	}
}
