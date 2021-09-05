<?php

namespace MM\View\Helper;

use MM\View\Exception;
use MM\View\Helper;

class MetaNameTags extends Helper implements \Countable {
	/**
	 * @var array
	 */
	protected $_container = [];

	/**
	 * @param $name
	 * @param $content
	 * @return $this
	 */
	public function set($name, $content) {
		$name = $this->_normalizeName($name);
		$this->_container[$name] = $content;
		return $this;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function has($name) {
		$name = $this->_normalizeName($name);
		return isset($this->_container[$name]);
	}

	/**
	 * @param $name
	 * @return string
	 */
	protected function _normalizeName($name) {
		return strtolower($name);
	}

	/**
	 * @return array
	 */
	public function getContainer() {
		return $this->_container;
	}

	/**
	 * @return int
	 */
	public function count() {
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

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
}
