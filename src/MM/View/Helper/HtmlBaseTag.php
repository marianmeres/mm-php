<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

use MM\View\Helper;
use MM\View\Exception;

class HtmlBaseTag extends Helper {
	/**
	 * @var string
	 */
	protected $_href;

	/**
	 * @var string
	 */
	protected $_target;

	/**
	 * @param null $href
	 * @param null $target
	 * @return $this
	 */
	public function __invoke($href = null, $target = null) {
		$href && $this->setHref($href);
		$target && $this->setTarget($target);
		return $this;
	}

	/**
	 * @param $href
	 * @return $this
	 */
	public function setHref($href) {
		$this->_href = $href;
		return $this;
	}

	/**
	 * @param $target
	 * @return $this
	 */
	public function setTarget($target) {
		$this->_target = $target;
		return $this;
	}

	/**
	 * @return string
	 */
	public function toString() {
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

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
}
