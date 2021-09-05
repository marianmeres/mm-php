<?php
namespace MM\View\Helper;

class HeadTitle extends ContainerOfStrings {
	protected string $_separator = ' | ';
	protected bool $_unique = false;
	protected bool $_doEscape = true;

	public function toString(): string {
		return '<title>' .
			implode($this->_separator, $this->_getMaybeEscaped()) .
			"</title>\n";
	}
}
