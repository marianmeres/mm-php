<?php
namespace MM\Util;

class Uuid {
	/**
	 * http://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid
	 *
	 * Note: pokial tomu spravne rozumiem, toto sice vyrobi skutocne nahodny a formalne
	 * spravny uuid, neviem vsak ci to koser z "datoveho" pohladu, lebo prve byty
	 * by mali byt timestamp based...
	 *
	 * @return string
	 */
	public static function get() {
		$data = openssl_random_pseudo_bytes(16);

		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10

		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	/**
	 * Zbuchane na rychlo... dorobit konfiguraciu via optiony
	 * @param int $length
	 * @param array|null $options
	 * @return string
	 */
	public static function getShortUid($length = 10, array $options = null) {
		$c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . 'abcdefghijklmnopqrstuvwxyz' . '0123456789';
		//. '-_'

		// todo: handle options

		// vyhodime human ambiguos citatelne
		$c = preg_replace('/(l|1|I|0|O)/', '', $c);
		$max = strlen($c) - 1;

		$length = min(100, abs((int) $length)); // safety break
		$out = '';

		for ($i = 0; $i < $length; $i++) {
			// todo: pre php7 pouzit random_int
			$pos = mt_rand(0, $max);
			$out .= $c[$pos];
		}

		return $out;
	}
}
