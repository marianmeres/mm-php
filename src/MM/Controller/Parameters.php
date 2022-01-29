<?php
declare(strict_types=1);

namespace MM\Controller;

/**
 * Ported/Inspired from Zend\Stdlib\Parameters
 */

class Parameters extends \ArrayObject {
	/**
	 * Enforces that we have an array, and enforces parameter access to array
	 * elements.
	 */
	public function __construct(array $values = null) {
		if (null === $values) {
			$values = [];
		}
		parent::__construct($values, \ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Populate from native PHP array
	 */
	public function fromArray(array $values): static {
		$this->exchangeArray($values);
		return $this;
	}

	/**
	 * Populate from query string
	 */
	public function fromString(string $string): static {
		$array = [];
		parse_str($string, $array);
		$this->fromArray($array);
		return $this;
	}

	/**
	 * Serialize to native PHP array
	 */
	public function toArray(): array {
		return $this->getArrayCopy();
	}

	/**
	 * Serialize to query string
	 */
	public function toString(): string {
		return http_build_query($this);
	}

	/**
	 * Retrieve by key
	 *
	 * Returns null if the key does not exist.
	 */
	public function offsetGet($key): mixed {
		if (isset($this[$key])) {
			return parent::offsetGet($key);
		}
		return null;
	}

	public function get($name, $default = null) {
		if (isset($this[$name])) {
			return parent::offsetGet($name);
		}
		return $default;
	}

	public function set(string $name, $value): static {
		$this[$name] = $value;
		return $this;
	}
}
