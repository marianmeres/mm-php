<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

/**
 * Class ContainerOfStrings
 * @package MM\View\Helper
 */
class ContainerOfStrings extends Helper implements \Countable {
	/**
	 * @var array
	 */
	protected $_container = [];

	/**
	 * @var string
	 */
	protected $_separator = '';

	/**
	 * Whether to force unique vals in container or not
	 * @var bool
	 */
	protected $_unique = true;

	/**
	 * @var bool
	 */
	protected $_escape = true;

	/**
	 * @param null $strings
	 * @param string $method
	 * @param null $escape
	 * @return $this
	 * @throws Exception
	 */
	public function __invoke($strings = null, $method = 'append', $escape = null) {
		if ($strings) {
			if (!preg_match('/append|prepend|replace/', $method)) {
				throw new Exception("Unknown method '$method'");
			}
			$this->$method($strings, $escape);
		}
		return $this;
	}

	/**
	 * @param null $strings
	 * @param null $escape
	 * @return $this
	 */
	public function replace($strings = null, $escape = null) {
		$this->_container = [];
		if ($strings) {
			$this->append($strings, $escape);
		}
		return $this;
	}

	/**
	 * @param $strings
	 * @param null $escape
	 * @return $this
	 */
	public function append($strings, $escape = null) {
		if (null === $escape) {
			$escape = $this->_escape;
		}

		foreach ((array) $strings as $string) {
			if ($escape) {
				$string = $this->_escape($string);
			}
			$this->_container[] = $string;
		}

		if ($this->_unique) {
			$this->_container = array_unique($this->_container);
		}

		return $this;
	}

	/**
	 * @param $strings
	 * @param null $escape
	 * @return $this
	 */
	public function prepend($strings, $escape = null) {
		if (null === $escape) {
			$escape = $this->_escape;
		}

		//
		$strings = array_reverse((array) $strings);
		foreach ($strings as $string) {
			if ($escape) {
				$string = $this->_escape($string);
			}
			array_unshift($this->_container, $string);
		}

		if ($this->_unique) {
			$this->_container = array_unique($this->_container);
		}

		return $this;
	}

	/**
	 * @param $strings
	 * @return $this
	 */
	public function remove($strings) {
		$this->_container = array_diff($this->_container, (array) $strings);
		return $this;
	}

	/**
	 * @param array $container
	 * @return $this
	 */
	public function setContainer(array $container) {
		$this->_container = $container;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getContainer() {
		return $this->_container;
	}

	/**
	 * @param $val
	 * @return string
	 */
	protected function _escape($val) {
		return htmlspecialchars($val, ENT_COMPAT, 'UTF-8');
	}

	/**
	 * @param $flag
	 * @return $this
	 */
	public function setEscape($flag) {
		$this->_escape = (bool) $flag;
		return $this;
	}

	/**
	 * @param $flag
	 * @return $this
	 */
	public function setUnique($flag) {
		$this->_unique = (bool) $flag;
		return $this;
	}

	/**
	 * @param $sep
	 * @return $this
	 */
	public function setSeparator($sep) {
		$this->_separator = $sep;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function reverse() {
		$this->_container = array_reverse($this->_container);
		return $this;
	}

	/**
	 * @return int
	 */
	public function count() {
		return count($this->_container);
	}

	/**
	 * To be overridden
	 */
	public function toString() {
		return implode($this->_separator, $this->_container);
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
