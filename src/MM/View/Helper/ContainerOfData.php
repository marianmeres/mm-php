<?php declare(strict_types=1);

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class ContainerOfData extends Helper implements \Countable {
	protected array $_container = [];

	// To be extended. Default noop.
	protected function _validateAndNormalizeData($data) {
		return $data;
	}

	public function append($data): static {
		$data = $this->_validateAndNormalizeData($data);
		$this->_container[] = $data;
		return $this;
	}

	public function prepend($data): static {
		$data = $this->_validateAndNormalizeData($data);
		array_unshift($this->_container, $data);
		return $this;
	}

	public function setContainer(array $container): static {
		$this->_container = $container;
		return $this;
	}

	public function getContainer(): array {
		return $this->_container;
	}

	public function count(): int {
		return count($this->_container);
	}

	public function reverse(): static {
		$this->_container = array_reverse($this->_container);
		return $this;
	}

	/**
	 * To be overridden
	 */
	public function toString(): bool|string {
		return print_r($this->_container, true);
	}

	/**
	 * to avoid ambiguos "method __toString cannot throw exceptions" use
	 * the above toString
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
}
