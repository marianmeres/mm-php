<?php declare(strict_types=1);

/**
 * Portnute veci z webu roznych miest do MM\Util
 * @author www
 */
namespace MM\Util;

class Utf8Str {
	/**
	 * @param $str
	 * @return string
	 */
	public static function replaceInvalidByteSequence($str) {
		// save current value
		$old = mb_substitute_character();

		// http://www.unicode.org/reports/tr36/#Substituting_for_Ill_Formed_Subsequences
		// http://stackoverflow.com/questions/8215050/replacing-invalid-utf-8-characters-by-question-marks-mbstring-substitute-charac
		mb_substitute_character(0xfffd);
		$str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');

		// restore previous
		mb_substitute_character($old);

		return $str;
	}

	/**
	 *
	 * @param $s
	 * @return bool
	 */
	public static function isUtf8($s) {
		for ($i = 0; $i < strlen($s); $i++) {
			if (ord($s[$i]) < 0x80) {
				continue;
			}
			# 0bbbbbbb
			elseif ((ord($s[$i]) & 0xe0) == 0xc0) {
				$n = 1;
			}
			# 110bbbbb
			elseif ((ord($s[$i]) & 0xf0) == 0xe0) {
				$n = 2;
			}
			# 1110bbbb
			elseif ((ord($s[$i]) & 0xf8) == 0xf0) {
				$n = 3;
			}
			# 11110bbb
			elseif ((ord($s[$i]) & 0xfc) == 0xf8) {
				$n = 4;
			}
			# 111110bb
			elseif ((ord($s[$i]) & 0xfe) == 0xfc) {
				$n = 5;
			}
			# 1111110b
			else {
				return false;
			} # Does not match any model
			for ($j = 0; $j < $n; $j++) {
				# n bytes matching 10bbbbbb follow ?
				if (++$i == strlen($s) || (ord($s[$i]) & 0xc0) != 0x80) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Ak je intl extensiona tak pouzije ju, inak fallbackuje na manualne
	 * riesenie
	 *
	 * @param $str
	 * @return mixed|string
	 */
	public static function unaccent($str) {
		if (class_exists('Normalizer', false)) {
			return self::normalizeUnaccentUtf8String($str);
		}

		return self::unaccentUtf8String($str);
	}

	/**
	 * Tato fnc vyuziva Nomrmalizer (od php 5.3), ktory musi byt aktivny (
	 * intl exntension). Je asi o polovicu rychlejsie ako ta druha varianta.
	 *
	 * Otazka je, ako sa toto bude chovat pri veeelmi dlhych stringoch
	 * (preg_replace...) Keby to padlo by som sa necudoval...
	 *
	 * @param $s
	 * @return mixed
	 */
	public static function normalizeUnaccentUtf8String($s) {
		$original = $s;

		// Normalizer-class missing!
		if (!class_exists('Normalizer', $autoload = false)) {
			return $original;
		}

		// maps German (umlauts) and other European characters onto two
		// characters before just removing diacritics
		// $s = preg_replace('@\x{00c4}@u', "AE", $s); // umlaut Ä => AE // pre slovencinu je toto blbost
		$s = preg_replace('@\x{00d6}@u', 'OE', $s); // umlaut Ö => OE
		$s = preg_replace('@\x{00dc}@u', 'UE', $s); // umlaut Ü => UE
		// $s = preg_replace('@\x{00e4}@u', "ae", $s); // umlaut ä => ae // pre slovencinu je toto blbost
		$s = preg_replace('@\x{00f6}@u', 'oe', $s); // umlaut ö => oe
		$s = preg_replace('@\x{00fc}@u', 'ue', $s); // umlaut ü => ue
		$s = preg_replace('@\x{00f1}@u', 'ny', $s); // ñ => ny
		$s = preg_replace('@\x{00ff}@u', 'yu', $s); // ÿ => yu

		// maps special characters (characters with diacritics) on their
		// base-character followed by the diacritical mark
		// exmaple:  Ú => U´,  á => a`
		$s = \Normalizer::normalize($s, \Normalizer::FORM_D);

		// Kriticka cast..
		// http://php.net/manual/en/regexp.reference.unicode.php
		// \pM -> "Mark"
		$s = preg_replace('@\pM@u', '', $s); // removes diacritics

		//
		$s = preg_replace('@\x{00df}@u', 'ss', $s); // maps German ß onto ss
		$s = preg_replace('@\x{00c6}@u', 'AE', $s); // Æ => AE
		$s = preg_replace('@\x{00e6}@u', 'ae', $s); // æ => ae
		$s = preg_replace('@\x{0132}@u', 'IJ', $s); // ? => IJ
		$s = preg_replace('@\x{0133}@u', 'ij', $s); // ? => ij
		$s = preg_replace('@\x{0152}@u', 'OE', $s); // Œ => OE
		$s = preg_replace('@\x{0153}@u', 'oe', $s); // œ => oe

		$s = preg_replace('@\x{00d0}@u', 'D', $s); // Ð => D
		$s = preg_replace('@\x{0110}@u', 'D', $s); // Ð => D
		$s = preg_replace('@\x{00f0}@u', 'd', $s); // ð => d
		$s = preg_replace('@\x{0111}@u', 'd', $s); // d => d
		$s = preg_replace('@\x{0126}@u', 'H', $s); // H => H
		$s = preg_replace('@\x{0127}@u', 'h', $s); // h => h
		$s = preg_replace('@\x{0131}@u', 'i', $s); // i => i
		$s = preg_replace('@\x{0138}@u', 'k', $s); // ? => k
		$s = preg_replace('@\x{013f}@u', 'L', $s); // ? => L
		$s = preg_replace('@\x{0141}@u', 'L', $s); // L => L
		$s = preg_replace('@\x{0140}@u', 'l', $s); // ? => l
		$s = preg_replace('@\x{0142}@u', 'l', $s); // l => l
		$s = preg_replace('@\x{014a}@u', 'N', $s); // ? => N
		$s = preg_replace('@\x{0149}@u', 'n', $s); // ? => n
		$s = preg_replace('@\x{014b}@u', 'n', $s); // ? => n
		$s = preg_replace('@\x{00d8}@u', 'O', $s); // Ø => O
		$s = preg_replace('@\x{00f8}@u', 'o', $s); // ø => o
		$s = preg_replace('@\x{017f}@u', 's', $s); // ? => s
		$s = preg_replace('@\x{00de}@u', 'T', $s); // Þ => T
		$s = preg_replace('@\x{0166}@u', 'T', $s); // T => T
		$s = preg_replace('@\x{00fe}@u', 't', $s); // þ => t
		$s = preg_replace('@\x{0167}@u', 't', $s); // t => t

		// remove all non-ASCii characters
		$s = preg_replace('@[^\0-\x80]@u', '', $s);

		// possible errors in UTF8-regular-expressions
		if (empty($s)) {
			return $original;
		} else {
			return $s;
		}
	}

	/**
	 * Toto robi to iste co vyssie func, akurat to robi manualne (definuje
	 * mapu a potom strtr). Trva raz tak dlho ako vyssia, ale nezavisi na
	 * php intl extensione
	 *
	 * @param  string $string  String to unaccent
	 * @return string $string  Unaccented string
	 */
	public static function unaccentUtf8String($string) {
		if (!preg_match('/[\x80-\xff]/', $string)) {
			return $string;
		}

		if (self::isUtf8($string)) {
			$chars = [
				// Decompositions for Latin-1 Supplement
				chr(195) . chr(128) => 'A',
				chr(195) . chr(129) => 'A',
				chr(195) . chr(130) => 'A',
				chr(195) . chr(131) => 'A',
				chr(195) . chr(132) => 'A',
				chr(195) . chr(133) => 'A',
				chr(195) . chr(135) => 'C',
				chr(195) . chr(136) => 'E',
				chr(195) . chr(137) => 'E',
				chr(195) . chr(138) => 'E',
				chr(195) . chr(139) => 'E',
				chr(195) . chr(140) => 'I',
				chr(195) . chr(141) => 'I',
				chr(195) . chr(142) => 'I',
				chr(195) . chr(143) => 'I',
				chr(195) . chr(145) => 'N',
				chr(195) . chr(146) => 'O',
				chr(195) . chr(147) => 'O',
				chr(195) . chr(148) => 'O',
				chr(195) . chr(149) => 'O',
				chr(195) . chr(150) => 'O',
				chr(195) . chr(153) => 'U',
				chr(195) . chr(154) => 'U',
				chr(195) . chr(155) => 'U',
				chr(195) . chr(156) => 'U',
				chr(195) . chr(157) => 'Y',
				chr(195) . chr(159) => 's',
				chr(195) . chr(160) => 'a',
				chr(195) . chr(161) => 'a',
				chr(195) . chr(162) => 'a',
				chr(195) . chr(163) => 'a',
				chr(195) . chr(164) => 'a',
				chr(195) . chr(165) => 'a',
				chr(195) . chr(167) => 'c',
				chr(195) . chr(168) => 'e',
				chr(195) . chr(169) => 'e',
				chr(195) . chr(170) => 'e',
				chr(195) . chr(171) => 'e',
				chr(195) . chr(172) => 'i',
				chr(195) . chr(173) => 'i',
				chr(195) . chr(174) => 'i',
				chr(195) . chr(175) => 'i',
				chr(195) . chr(177) => 'n',
				chr(195) . chr(178) => 'o',
				chr(195) . chr(179) => 'o',
				chr(195) . chr(180) => 'o',
				chr(195) . chr(181) => 'o',
				chr(195) . chr(182) => 'o',
				chr(195) . chr(182) => 'o',
				chr(195) . chr(185) => 'u',
				chr(195) . chr(186) => 'u',
				chr(195) . chr(187) => 'u',
				chr(195) . chr(188) => 'u',
				chr(195) . chr(189) => 'y',
				chr(195) . chr(191) => 'y',
				// Decompositions for Latin Extended-A
				chr(196) . chr(128) => 'A',
				chr(196) . chr(129) => 'a',
				chr(196) . chr(130) => 'A',
				chr(196) . chr(131) => 'a',
				chr(196) . chr(132) => 'A',
				chr(196) . chr(133) => 'a',
				chr(196) . chr(134) => 'C',
				chr(196) . chr(135) => 'c',
				chr(196) . chr(136) => 'C',
				chr(196) . chr(137) => 'c',
				chr(196) . chr(138) => 'C',
				chr(196) . chr(139) => 'c',
				chr(196) . chr(140) => 'C',
				chr(196) . chr(141) => 'c',
				chr(196) . chr(142) => 'D',
				chr(196) . chr(143) => 'd',
				chr(196) . chr(144) => 'D',
				chr(196) . chr(145) => 'd',
				chr(196) . chr(146) => 'E',
				chr(196) . chr(147) => 'e',
				chr(196) . chr(148) => 'E',
				chr(196) . chr(149) => 'e',
				chr(196) . chr(150) => 'E',
				chr(196) . chr(151) => 'e',
				chr(196) . chr(152) => 'E',
				chr(196) . chr(153) => 'e',
				chr(196) . chr(154) => 'E',
				chr(196) . chr(155) => 'e',
				chr(196) . chr(156) => 'G',
				chr(196) . chr(157) => 'g',
				chr(196) . chr(158) => 'G',
				chr(196) . chr(159) => 'g',
				chr(196) . chr(160) => 'G',
				chr(196) . chr(161) => 'g',
				chr(196) . chr(162) => 'G',
				chr(196) . chr(163) => 'g',
				chr(196) . chr(164) => 'H',
				chr(196) . chr(165) => 'h',
				chr(196) . chr(166) => 'H',
				chr(196) . chr(167) => 'h',
				chr(196) . chr(168) => 'I',
				chr(196) . chr(169) => 'i',
				chr(196) . chr(170) => 'I',
				chr(196) . chr(171) => 'i',
				chr(196) . chr(172) => 'I',
				chr(196) . chr(173) => 'i',
				chr(196) . chr(174) => 'I',
				chr(196) . chr(175) => 'i',
				chr(196) . chr(176) => 'I',
				chr(196) . chr(177) => 'i',
				chr(196) . chr(178) => 'IJ',
				chr(196) . chr(179) => 'ij',
				chr(196) . chr(180) => 'J',
				chr(196) . chr(181) => 'j',
				chr(196) . chr(182) => 'K',
				chr(196) . chr(183) => 'k',
				chr(196) . chr(184) => 'k',
				chr(196) . chr(185) => 'L',
				chr(196) . chr(186) => 'l',
				chr(196) . chr(187) => 'L',
				chr(196) . chr(188) => 'l',
				chr(196) . chr(189) => 'L',
				chr(196) . chr(190) => 'l',
				chr(196) . chr(191) => 'L',
				chr(197) . chr(128) => 'l',
				chr(197) . chr(129) => 'L',
				chr(197) . chr(130) => 'l',
				chr(197) . chr(131) => 'N',
				chr(197) . chr(132) => 'n',
				chr(197) . chr(133) => 'N',
				chr(197) . chr(134) => 'n',
				chr(197) . chr(135) => 'N',
				chr(197) . chr(136) => 'n',
				chr(197) . chr(137) => 'N',
				chr(197) . chr(138) => 'n',
				chr(197) . chr(139) => 'N',
				chr(197) . chr(140) => 'O',
				chr(197) . chr(141) => 'o',
				chr(197) . chr(142) => 'O',
				chr(197) . chr(143) => 'o',
				chr(197) . chr(144) => 'O',
				chr(197) . chr(145) => 'o',
				chr(197) . chr(146) => 'OE',
				chr(197) . chr(147) => 'oe',
				chr(197) . chr(148) => 'R',
				chr(197) . chr(149) => 'r',
				chr(197) . chr(150) => 'R',
				chr(197) . chr(151) => 'r',
				chr(197) . chr(152) => 'R',
				chr(197) . chr(153) => 'r',
				chr(197) . chr(154) => 'S',
				chr(197) . chr(155) => 's',
				chr(197) . chr(156) => 'S',
				chr(197) . chr(157) => 's',
				chr(197) . chr(158) => 'S',
				chr(197) . chr(159) => 's',
				chr(197) . chr(160) => 'S',
				chr(197) . chr(161) => 's',
				chr(197) . chr(162) => 'T',
				chr(197) . chr(163) => 't',
				chr(197) . chr(164) => 'T',
				chr(197) . chr(165) => 't',
				chr(197) . chr(166) => 'T',
				chr(197) . chr(167) => 't',
				chr(197) . chr(168) => 'U',
				chr(197) . chr(169) => 'u',
				chr(197) . chr(170) => 'U',
				chr(197) . chr(171) => 'u',
				chr(197) . chr(172) => 'U',
				chr(197) . chr(173) => 'u',
				chr(197) . chr(174) => 'U',
				chr(197) . chr(175) => 'u',
				chr(197) . chr(176) => 'U',
				chr(197) . chr(177) => 'u',
				chr(197) . chr(178) => 'U',
				chr(197) . chr(179) => 'u',
				chr(197) . chr(180) => 'W',
				chr(197) . chr(181) => 'w',
				chr(197) . chr(182) => 'Y',
				chr(197) . chr(183) => 'y',
				chr(197) . chr(184) => 'Y',
				chr(197) . chr(185) => 'Z',
				chr(197) . chr(186) => 'z',
				chr(197) . chr(187) => 'Z',
				chr(197) . chr(188) => 'z',
				chr(197) . chr(189) => 'Z',
				chr(197) . chr(190) => 'z',
				chr(197) . chr(191) => 's',
				// Euro Sign
				chr(226) . chr(130) . chr(172) => 'E',
				// GBP (Pound) Sign
				chr(194) . chr(163) => '',
				//'Ä' => 'Ae', 'ä' => 'ae', // pre slovencinu je toto blbost
				'Ü' => 'Ue',
				'ü' => 'ue',
				'Ö' => 'Oe',
				'ö' => 'oe',
				'ß' => 'ss',
				// Norwegian characters
				'Å' => 'Aa',
				'Æ' => 'Ae',
				'Ø' => 'O',
				'æ' => 'a',
				'ø' => 'o',
				'å' => 'aa',
			];

			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] =
				chr(128) .
				chr(131) .
				chr(138) .
				chr(142) .
				chr(154) .
				chr(158) .
				chr(159) .
				chr(162) .
				chr(165) .
				chr(181) .
				chr(192) .
				chr(193) .
				chr(194) .
				chr(195) .
				chr(196) .
				chr(197) .
				chr(199) .
				chr(200) .
				chr(201) .
				chr(202) .
				chr(203) .
				chr(204) .
				chr(205) .
				chr(206) .
				chr(207) .
				chr(209) .
				chr(210) .
				chr(211) .
				chr(212) .
				chr(213) .
				chr(214) .
				chr(216) .
				chr(217) .
				chr(218) .
				chr(219) .
				chr(220) .
				chr(221) .
				chr(224) .
				chr(225) .
				chr(226) .
				chr(227) .
				chr(228) .
				chr(229) .
				chr(231) .
				chr(232) .
				chr(233) .
				chr(234) .
				chr(235) .
				chr(236) .
				chr(237) .
				chr(238) .
				chr(239) .
				chr(241) .
				chr(242) .
				chr(243) .
				chr(244) .
				chr(245) .
				chr(246) .
				chr(248) .
				chr(249) .
				chr(250) .
				chr(251) .
				chr(252) .
				chr(253) .
				chr(255);

			$chars['out'] =
				'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';

			$string = strtr($string, $chars['in'], $chars['out']);
			$doubleChars['in'] = [
				chr(140),
				chr(156),
				chr(198),
				chr(208),
				chr(222),
				chr(223),
				chr(230),
				chr(240),
				chr(254),
			];
			$doubleChars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
			$string = str_replace($doubleChars['in'], $doubleChars['out'], $string);
		}

		return $string;
	}
}
