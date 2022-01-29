<?php declare(strict_types=1);

namespace MM\Util;

interface TranslateInterface {

	public function translate(string $key, $replaceArgs = null): mixed;

	public function hasTranslationFor($key, $lang = null);
}
