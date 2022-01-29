<?php declare(strict_types=1);

namespace MM\Util;

use MM\Util\ClassUtil;

/**
 * Iba bazalne na rychlo
 */
class Translate implements TranslateInterface, \ArrayAccess {
	/**
	 * Interne data v tvare: "jazyk" => array("kluc" => "preklad")
	 */
	protected array $_data = [];

	/**
	 * Aktualny jazyk
	 */
	protected string $_lang = 'EN';

	/**
	 * Pixel loca tool convention
	 */
	public string $placeholder = 'XXX';

	public function __construct(array $options = null, $strict = true) {
		ClassUtil::setOptions($this, $options, $strict);
	}

	public function __invoke(): mixed {
		$args = func_get_args();
		return call_user_func_array([$this, 'translate'], $args);
	}

	public function setLang($lang): static {
		// TODO: validate here
		if (!preg_match('/^[a-z]{2}$/i', $lang)) {
			throw new \InvalidArgumentException("Invalid lang '$lang'");
		}
		$this->_lang = $this->_normalizeLang($lang);
		return $this;
	}

	protected function _normalizeLang(string $lang): string {
		return strtoupper($lang);
	}

	public function getLang(): string {
		return $this->_lang;
	}

	public function translate(string $key, $replaceArgs = null): mixed {
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
		return preg_replace_callback(
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
	}

	public function getData(): array {
		return $this->_data;
	}

	public function addTranslation(array $data, $lang = null): static {
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
	 */
	public function setTranslation(array $data, bool $reset = true): static {
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
	 */
	protected function _initializeData($lang): mixed {
		// nejake defaultne loady tu napr...

		// teraz iba takto
		if (!isset($this->_data[$lang])) {
			$this->_data[$lang] = [];
		}

		return $this->_data[$lang];
	}

	public function hasTranslationFor($key, $lang = null): bool {
		if (!$lang) {
			$lang = $this->_lang;
		}
		return isset($this->_data[$lang][$key]);
	}

	public function offsetGet(mixed $offset): mixed {
		return $this->translate($offset);
	}

	public function offsetSet(mixed $offset, mixed $value): void {
		$this->addTranslation([$offset => $value]);
	}

	public function offsetExists(mixed $offset): bool {
		return isset($this->_data[$this->getLang()][$offset]);
	}

	public function offsetUnset(mixed $offset): void {
		unset($this->_data[$this->getLang()][$offset]);
	}
}
