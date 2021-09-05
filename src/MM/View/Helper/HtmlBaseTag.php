<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;
use PhpParser\Node\Scalar\String_;

class HtmlBaseTag extends Helper {
	protected ?string $_href = null;

	protected ?string $_target = null;

	public function __invoke($href = null, $target = null): static {
		$href && $this->setHref($href);
		$target && $this->setTarget($target);
		return $this;
	}

	public function setHref($href): static {
		$this->_href = $href;
		return $this;
	}

	public function setTarget($target): static {
		$this->_target = $target;
		return $this;
	}

	public function toString(): string {
		$out = '';

		if ($this->_href !== null) {
			$out = "<base href='$this->_href'";
			if ($this->_target !== null) {
				$out .= " target='$this->_target'";
			}
			$out .= "/>\n";
		}

		return $out;
	}

	public function __toString() {
		return $this->toString();
	}
}
