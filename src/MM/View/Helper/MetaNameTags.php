<?php

namespace MM\View\Helper;

use MM\View\Exception;
use MM\View\Helper;

class MetaNameTags extends Helper implements \Countable {
	protected array $_container = [];

	public function set($name, $content): static {
		$name = $this->_normalizeName($name);
		$this->_container[$name] = $content;
		return $this;
	}

	public function has($name): bool {
		$name = $this->_normalizeName($name);
		return isset($this->_container[$name]);
	}

	protected function _normalizeName($name): string {
		return strtolower($name);
	}

	public function getContainer(): array {
		return $this->_container;
	}

	public function count(): int {
		return count($this->_container);
	}

	public function toString(): string {
		$out = '';

		foreach ($this->_container as $name => $content) {
			$out .= sprintf(
				"<meta name='$name' content='%s'/>\n",
				htmlspecialchars($content),
			);
		}

		return $out;
	}

	public function __toString(): string {
		return $this->toString();
	}
}
