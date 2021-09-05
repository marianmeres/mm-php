<?php declare(strict_types=1);

namespace MM\View\Helper;

class HeadCss extends ContainerOfStrings {
	protected bool $_unique = false;

	protected bool $_doEscape = false;

	public function toString(): string {
		$out = '';
		foreach ($this->_getMaybeEscaped() as $css) {
			$out .= "<style>\n$css\n</style>\n";
		}
		return $out;
	}
}
