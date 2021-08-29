<?php declare(strict_types=1);

namespace MM\Util;

class Str {
	public static function startsWith($haystack, $needle): bool
	{
		return str_starts_with($haystack, $needle);
		// $length = strlen($needle);
		// return substr($haystack, 0, $length) === $needle;
	}

	public static function endsWith($haystack, $needle): bool
	{
		return str_ends_with($haystack, $needle);
		// $length = strlen($needle);
		// if (!$length) {
		// 	return true;
		// }
		// return substr($haystack, -$length) === $needle;
	}
}
