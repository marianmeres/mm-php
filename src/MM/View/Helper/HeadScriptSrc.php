<?php declare(strict_types=1);

namespace MM\View\Helper;

class HeadScriptSrc extends ContainerOfStrings {
	protected bool $_unique = true;

	protected bool $_doEscape = false;

	public function toString(): string {
		$out = '';
		foreach ($this->_getMaybeEscaped() as $src) {
			$out .= "<script src='$src'></script>\n";
		}
		return $out;
	}
}
