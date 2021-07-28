<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

class HtmlTagClass extends ContainerOfStrings
{
	/**
	 * @var bool
	 */
	protected $_unique = true;

	/**
	 * @var string
	 */
	protected $_separator = ' ';

	/**
	 * @return string
	 */
	public function toString()
	{
		return implode($this->_separator, $this->_container);
	}
}
