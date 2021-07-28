<?php
/**
 * @author Marian Meres
 */
namespace MM\View\Helper;

/**
 * Class HeadScript
 * @package MM\View\Helper
 */
class HeadScript extends ContainerOfStrings
{
	/**
	 * @var bool
	 */
	protected $_unique = false;

	/**
	 * @var bool
	 */
	protected $_escape = false;

	/**
	 * @return string
	 */
	public function toString()
	{
		$out = '';
		foreach ($this->_container as $string) {
			$out .= "<script>\n$string\n</script>\n";
		}
		return $out;
	}
}
