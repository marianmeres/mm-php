<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

class Nbsp {
	/**
	 * Doplni &nbsp; tam kde treba... najskor vsak nebude dokonale, a vsetky
	 * hranicne case-y urcite teraz nevidim...
	 *
	 * @param $s
	 * @return mixed
	 */
	public static function apply($s, $debug = false) {
		//$rg = "/(^|\W+|\s+)(\w)\s+(\w+)/imsu";
		$rg = implode('', [
			// zaciatok, alebo whitespace+, alebo non-word+
			//"/(^|\s+|[^a-zA-Z0-9-<>\.,;\+_]+)", // toto nejak blbne na diakritike
			//"/(^|[\W]+|\s+)", // toto neslape pri <a href=
			//"/(^|\s+|\P{L}+)", // tu je rovnaky problem ako s \W
			// kedze "<" (co je casty use case) je tiez non-word, musime listovat vsetko explicitne
			"/(^|\s+|[^\p{L}0-9<>§±@#\$%\^&*\(\)\.\?\!\\\,;:\+\-=_\/'\|\{\}\[\]]+)",
			// nasledovany presne jednym word charom s whitespacom+ a dalsim(i) word charmi
			'(\w)\s+(\w+)',
			// case insensitive, multiline, including new line, unicode,
			'/imsu',
		]);

		if ($debug) {
			if (preg_match($rg, $s, $m)) {
				print_r($m);
				exit();
			}
			return $s;
		}

		return preg_replace($rg, '$1$2&nbsp;$3', $s);
	}
}
// ,./<>?;'\|":[]}{-=1
