<?php declare(strict_types=1);

namespace MM\View\Helper;

class BodyTagClass extends ContainerOfStrings {
	protected bool $_unique = true;

	protected string $_separator = ' ';

	public function toString(): string {
		return implode($this->_separator, $this->_getMaybeEscaped());
	}
}
