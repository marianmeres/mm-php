<?php declare(strict_types=1);

namespace MM\Util;

interface TranslateInterface {

	public function translate($key, $replaceArgs = null);

	public function hasTranslationFor($key, $lang = null);
}
