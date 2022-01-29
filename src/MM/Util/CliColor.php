<?php declare(strict_types=1);

namespace MM\Util;

/**
 * Class CliColor
 *
 * zobrate z https://gist.github.com/superbrothers/3431198 a trosku upravene
 *
 * pouzitie:
 */
class CliColor {
	protected static array $_ANSI_CODES = [
		'off' => 0,
		'bold' => 1,
		'italic' => 3,
		'underline' => 4,
		'blink' => 5,
		'inverse' => 7,
		'hidden' => 8,

		'black' => 30,
		'red' => 31,
		'green' => 32,
		'yellow' => 33,
		'blue' => 34,
		'magenta' => 35,
		'cyan' => 36,
		'white' => 37,

		'black_bg' => 40,
		'red_bg' => 41,
		'green_bg' => 42,
		'yellow_bg' => 43,
		'blue_bg' => 44,
		'magenta_bg' => 45,
		'cyan_bg' => 46,
		'white_bg' => 47,
	];

	public static function set($str, $color): string {
		$colors = explode('+', $color);
		$out = '';
		$isColored = false;

		foreach ($colors as $color) {
			if (isset(self::$_ANSI_CODES[$color])) {
				$out .= "\033[" . self::$_ANSI_CODES[$color] . 'm';
				$isColored = true;
			}
		}

		$out .= $str;

		if ($isColored) {
			$out .= "\033[" . self::$_ANSI_CODES['off'] . 'm';
		}

		return $out;
	}

	/**
	 * nizsie su aliasy
	 */

	public static function red($s): string {
		return self::set($s, 'red');
	}

	public static function green($s): string {
		return self::set($s, 'green');
	}

	public static function yellow($s): string {
		return self::set($s, 'yellow');
	}

	public static function blue($s): string {
		return self::set($s, 'blue');
	}

	public static function magenta($s): string {
		return self::set($s, 'magenta');
	}

	public static function cyan($s): string {
		return self::set($s, 'cyan');
	}

	public static function white($s): string {
		return self::set($s, 'white');
	}
}

//echo CliColor::red("red") . "\n";
//echo CliColor::green("green") . "\n";
//echo CliColor::yellow("yellow") . "\n";
//echo CliColor::blue("blue") . "\n";
//echo CliColor::magenta("magenta") . "\n";
//echo CliColor::cyan("cyan") . "\n";
//echo CliColor::white("white") . "\n";
//echo CliColor::set("foo", 'yellow+magenta_bg') . "\n";
