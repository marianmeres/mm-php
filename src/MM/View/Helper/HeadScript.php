<?php

namespace MM\View\Helper;

class HeadScript extends ContainerOfStrings {
	protected bool $_unique = false;

	protected bool $_doEscape = false;

	public function toString(): string {
		$out = '';
		foreach ($this->_container as $string) {
			$out .= "<script>\n$string\n</script>\n";
		}
		return $out;
	}
}
