<?php declare(strict_types=1);

namespace MM\Util;

use MM\Util\ClassUtil;

/**
 * Iba bazalne na rychlo
 */
class Translate implements TranslateInterface, \ArrayAccess {
	/**
	 * Interne data v tvare: "jazyk" => array("kluc" => "preklad")
	 * @var array
	 */
	protected $_data = [];

	/**
	 * Aktualny jazyk
	 * @var string
	 */
	protected $_lang = 'EN';

	/**
	 * Pixel loca tool convention
	 * @var string
	 */
	public $placeholder = 'XXX';

	/**
	 * @param array $options
	 */
	public function __construct(array $options = null, $strict = true) {
		ClassUtil::setOptions($this, $options, $strict);
	}

	/**
	 * @return mixed
	 */
	public function __invoke() {
		$args = func_get_args();
		return call_user_func_array([$this, 'translate'], $args);
	}

	/**
	 * @param $lang
	 * @return $this
	 * @throws \InvalidArgumentException
	 */
	public function setLang($lang) {
		// TODO: validate here
		if (!preg_match('/^[a-z]{2}$/i', $lang)) {
			throw new \InvalidArgumentException("Invalid lang '$lang'");
		}
		$this->_lang = $this->_normalizeLang($lang);
		return $this;
	}

	/**
	 * @param $lang
	 * @return string
	 */
	protected function _normalizeLang($lang) {
		return strtoupper($lang);
	}

	/**
	 * @return string
	 */
	public function getLang() {
		return $this->_lang;
	}

	/**
	 * @param $key
	 * @param null $replaceArgs
	 * @return mixed
	 */
	public function translate($key, $replaceArgs = null) {
		$replace = (array) $replaceArgs;

		// podporujeme array aj arguments notaciu
		$args = func_get_args();
		$args = array_slice($args, 2);
		foreach ($args as $rep) {
			$replace[] = $rep;
		}

		$data = $this->_initializeData($this->_lang);

		if (!isset($data["$key"])) {
			return $key;
		}

		$str = $data["$key"];

		// pixel featura
		$str = preg_replace_callback(
			"/($this->placeholder)/",
			function ($m) use (&$replace) {
				if (empty($replace)) {
					return $m[1];
				}
				$out = current($replace);
				unset($replace[key($replace)]);
				return $out;
			},
			$str,
		);

		return $str;
	}

	/**
	 * @return array
	 */
	public function getData() {
		return $this->_data;
	}

	/**
	 * @param array $data
	 * @param null $lang
	 * @return $this
	 */
	public function addTranslation(array $data, $lang = null) {
		if (null === $lang) {
			$lang = $this->_lang;
		}

		$lang = $this->_normalizeLang($lang);

		if (!isset($this->_data[$lang])) {
			$this->_data[$lang] = [];
		}

		// $this->_data[$lang] = array_merge((array) $this->_data[$lang], $data);
		// tu rucny loop nie merge lebo chceme forcnut stringove kluce
		foreach ($data as $k => $v) {
			if (null !== $v) {
				$this->_data[$lang]["$k"] = "$v";
			} else {
				unset($this->_data[$lang]["$k"]);
			}
		}

		return $this;
	}

	/**
	 * Feature: data mozu by v tvare:
	 *     lang => array(key => value)
	 * alebo:
	 *     key => value // tu bude pouzity akutalny jazyk
	 *
	 * @param array $data
	 * @param bool $reset
	 * @return $this
	 */
	public function setTranslation(array $data, $reset = true) {
		if ($reset) {
			$this->_data = [];
		}

		// foreach ($data as $lang => $data2) {
		foreach ($data as $keyOrLang => $valueOrData) {
			if (is_array($valueOrData)) {
				// prx($valueOrData);
				$this->addTranslation($valueOrData, $keyOrLang);
			} else {
				$this->addTranslation([$keyOrLang => $valueOrData], null);
			}
		}

		return $this;
	}

	/**
	 * Extension hook
	 *
	 * @param $lang
	 * @return mixed
	 */
	protected function _initializeData($lang) {
		// nejake defaultne loady tu napr...

		// teraz iba takto
		if (!isset($this->_data[$lang])) {
			$this->_data[$lang] = [];
		}

		return $this->_data[$lang];
	}

	/**
	 * @param $key
	 * @param null $lang
	 * @return bool
	 */
	public function hasTranslationFor($key, $lang = null) {
		if (!$lang) {
			$lang = $this->_lang;
		}
		return isset($this->_data[$lang][$key]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->translate($offset);
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return $this|void
	 */
	public function offsetSet($offset, $value) {
		return $this->addTranslation([$offset => $value]);
	}

	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->_data[$this->getLang()][$offset]);
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset) {
		unset($this->_data[$this->getLang()][$offset]);
	}
}
