<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

abstract class LinkRelUnique extends Helper {
	protected ?string $_rel = null;

	protected ?string $_href = null;

	public function __invoke($href = null): static {
		$href && $this->setHref($href);
		return $this;
	}

	public function setHref($href): static {
		$this->_href = $href;
		return $this;
	}

	public function getHref(): ?string {
		return $this->_href;
	}

	public function toString(): string {
		$out = '';

		if ($this->_href !== null) {
			$out = "<link rel='$this->_rel' href='$this->_href'/>\n";
		}

		return $out;
	}

	public function __toString() {
		return $this->toString();
	}
}
