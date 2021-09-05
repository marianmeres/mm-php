<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class ContainerOfStrings extends Helper implements \Countable {
	protected array $_container = [];

	protected string $_separator = '';

	protected bool $_unique = true;

	protected bool $_doEscape = true;

	public function __invoke($strings = null, $method = 'append'): static {
		if (!empty($strings)) {
			if (!preg_match('/append|prepend|replace/', $method)) {
				throw new Exception("Unknown method '$method'");
			}
			$this->$method($strings);
		}
		return $this;
	}

	public function replace($strings): static {
		$this->_container = [];
		if (!empty($strings)) {
			$this->append($strings);
		}
		return $this;
	}

	public function append($strings): static {
		foreach ((array) $strings as $string) {
			if ($string !== null) { // feature!
				$this->_container[] = $string;
			}
		}

		if ($this->_unique) {
			$this->_container = array_unique($this->_container);
		}

		return $this;
	}

	public function prepend($strings): static {
		//
		$strings = array_reverse((array) $strings);
		foreach ($strings as $string) {
			array_unshift($this->_container, $string);
		}

		if ($this->_unique) {
			$this->_container = array_unique($this->_container);
		}

		return $this;
	}

	public function remove($strings): static {
		$this->_container = array_diff($this->_container, (array) $strings);
		return $this;
	}

	public function replaceLast(string|null $string): static {
		$lastIdx = max(count($this->_container) - 1, 0);

		// empty means unset
		if ($string === null || $string === '') {
			unset($this->_container[$lastIdx]);
			return $this;
		}

		$this->_container[$lastIdx] = $string;
		return $this;
	}

	public function setContainer(array $container): static {
		$this->_container = $container;
		return $this;
	}

	public function getContainer(): array {
		return $this->_container;
	}

	protected function _escaper($val): string {
		return htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
	}

	public function setDoEscape($flag): static {
		$this->_doEscape = (bool) $flag;
		return $this;
	}

	public function setUnique($flag): static {
		$this->_unique = (bool) $flag;
		return $this;
	}

	public function setSeparator($sep): static {
		$this->_separator = $sep;
		return $this;
	}

	public function reverse(): static {
		$this->_container = array_reverse($this->_container);
		return $this;
	}

	public function count(): int {
		return count($this->_container);
	}

	protected function _getMaybeEscaped(): array {
		if ($this->_doEscape) {
			return array_map(fn ($v) => $this->_escaper($v), $this->_container);
		}
		return $this->_container;
	}

	public function toString(): string {
		return implode($this->_separator, $this->_getMaybeEscaped());
	}

	/**
	 * to avoid ambiguos "method __toString cannot throw exceptions" use
	 * the above toString
	 */
	public function __toString() {
		return $this->toString();
	}
}
