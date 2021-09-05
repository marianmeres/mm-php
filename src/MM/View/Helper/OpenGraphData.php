<?php

namespace MM\View\Helper;

use MM\View\Exception;
use MM\View\Helper;

class OpenGraphData extends Helper implements \Countable {
	/**
	 * @var array
	 */
	protected $_whitelist = [
		// for facebook domain insights
		'fb:app_id' => 1,
		// basic
		'og:type' => 1,
		'og:url' => 1,
		'og:site_name' => 1,
		'og:title' => 1,
		'og:description' => 1,
		'og:image' => 1,
		// aditional
		'og:locale' => 1,
		'og:locale:alternate' => 1,
		// image (url je kesovana)
		'og:image:url' => 1,
		'og:image:secure_url' => 1,
		'og:image:type' => 1,
		'og:image:width' => 1,
		'og:image:height' => 1,
		// video
		'og:video' => 1,
		'og:video:url' => 1,
		'og:video:secure_url' => 1,
		'og:video:type' => 1,
		'og:video:width' => 1,
		'og:video:height' => 1,
	];

	/**
	 * @var array
	 */
	protected $_data = [];

	/**
	 * @return $this
	 */
	public function reset() {
		$this->_data = [];
		return $this;
	}

	/**
	 * @param $propertyOrData
	 * @param null $propertyContent
	 * @param bool|true $overwrite
	 * @return $this
	 * @throws Exception
	 */
	public function add($propertyOrData, $propertyContent = null, $overwrite = true) {
		if (is_array($propertyOrData)) {
			// note: $propertyContentOrOverwriteFlag ignored here
			foreach ($propertyOrData as $k => $v) {
				$this->_addOne($k, $v, $overwrite);
			}
		} else {
			$this->_addOne($propertyOrData, $propertyContent, $overwrite);
		}

		return $this;
	}

	/**
	 * @param $property
	 * @param $content
	 * @param bool|true $overwrite
	 * @return $this
	 * @throws Exception
	 */
	protected function _addOne($property, $content, $overwrite = true) {
		$property = strtolower($property);

		// add "og:" prefix if not provided
		if (substr($property, 0, 3) !== 'og:' && $property != 'fb:app_id') {
			$property = 'og:' . $property;
		}

		// is it whitelisted?
		if (!isset($this->_whitelist[$property])) {
			throw new Exception("Unknown og property '$property'");
		}

		if ($overwrite || !isset($this->_data[$property])) {
			$this->_data[$property] = $content;
		}

		return $this;
	}

	public function toString(): string {
		$out = '';

		foreach ($this->_data as $property => $content) {
			if (!empty($content)) {
				$out .= sprintf(
					//"<meta property='$property' content='%s'/>\n", htmlspecialchars($content)
					// zistujem, ze html nie je dobre...
					"<meta property='$property' content='%s'/>\n",
					strip_tags($content),
				);
			}
		}

		return $out;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * @return int
	 */
	public function count() {
		return count($this->_data);
	}
}
