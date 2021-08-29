<?php declare(strict_types=1);

namespace MM\Util;

class Hms {
	public static function get($timeInSeconds, $returnAsArray = false) {
		$s = $timeInSeconds % 60;
		$m = (($timeInSeconds - $s) % 3600) / 60;

		// note: nizsie dva zapisy su technicky ekvivalentne, ale pouzijem explicitny floor
		// $h = (int) ($timeInSeconds - $m) / 3600;
		// $h = (int) floor(($timeInSeconds - $m) / 3600);

		// note2: toto "($m * 60)" nie je potreben, ale davam to tam
		$h = (int) floor(($timeInSeconds - $m * 60) / 3600);

		if ($returnAsArray) {
			return [
				'h' => $h,
				'm' => $m,
				's' => $s,
			];
		}

		return sprintf('%02d:%02d:%02d', $h, $m, $s);
	}
}
