<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 * Interface TranslateInterface
 * @package MM\Util
 */
interface TranslateInterface {
	/**
	 * @param $key
	 * @param null $replaceArgs
	 * @return mixed
	 */
	public function translate($key, $replaceArgs = null);

	/**
	 * @param $key
	 * @param null $lang
	 * @return mixed
	 */
	public function hasTranslationFor($key, $lang = null);
}
