<?php
/**
 * Author: mm
 * Date: 22/07/15
 */

namespace MM\View\Helper;

use MM\View\Helper;

class LabeledContainersOfStrings extends Helper
{
	/**
	 * @var array
	 */
	protected $_containers = [];

	/**
	 * @param $label
	 * @param null $strings
	 * @return ContainerOfStrings
	 */
	public function __invoke($label, $strings = null)
	{
		/** @var ContainerOfStrings $cos */
		$cos = $this->get($label);
		$strings && $cos->append($strings);
		return $cos;
	}

	/**
	 * @param $label
	 * @param array|null $options
	 * @return mixed
	 */
	public function get($label, array $options = null)
	{
		$label = strtolower($label);

		if (!isset($this->_containers[$label])) {
			$cos = new ContainerOfStrings();
			$cos->setUnique(true);
			$cos->setSeparator(' ');
			$cos->setEscape(true);
			$this->_containers[$label] = $cos;
		}

		foreach (['separator', 'unique', 'escape'] as $optKey) {
			if (isset($options[$optKey])) {
				$setter = 'set' . ucfirst($optKey);
				$this->_containers[$label]->$setter($options[$optKey]);
			}
		}

		return $this->_containers[$label];
	}
}
