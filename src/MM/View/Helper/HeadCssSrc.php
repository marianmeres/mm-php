<?php declare(strict_types=1);

namespace MM\View\Helper;

class HeadCssSrc extends ContainerOfStrings {
	protected bool $_unique = true;

	protected bool $_doEscape = false;

	public function toString(): string {
		$out = '';
		foreach ($this->_container as $src) {
			$out .= "<link href='$src' rel='stylesheet'>\n";
		}
		return $out;
	}
}
