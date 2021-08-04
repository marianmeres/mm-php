<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 * Class Xml
 * @package MM\Util
 */
class Xml {
	/**
	 * Intentionally not using DOM, pure string stuff
	 *
	 * Features: escapes, supports @attributes, closes empty tags <el/> a
	 * correctly indents
	 *
	 * Acts as 1:1 oposite to xml2array below
	 *
	 * @param array $array
	 * @param string $rootName
	 * @param string $indent
	 * @return string
	 * @throws \Exception
	 */
	public static function array2xml(array $array, $rootName = 'root', $indent = '  ') {
		// skip formatting uplne
		if (is_bool($indent)) {
			$formatOutput = $indent;
			$indent = '  ';
		} elseif (!($formatOutput = (bool) preg_match('/^\s*$/', $indent))) {
			$indent = '  ';
		}

		$depth = $formatOutput ? 1 : 0; //
		$n = $formatOutput ? "\n" : ''; // new line char
		$out = '';
		$rootAttrs = '';

		/**
		 * @param $array
		 * @param $depth "depth level"
		 * @param $parent "parent tag name"
		 * @param string $attrs "attributes string"
		 * @param string $pAttrs "Parent attributes string"
		 * @return string
		 */
		$array2xml = function ($array, $depth, $parent, $attrs = '', $pAttrs = '') use (
			&$array2xml,
			$indent,
			$n,
			&$rootAttrs
		) {
			// lambda recursion

			static $first = true; // because root attributes

			// xattrs sluzia ako perzistentny pomocnik neresetujuci sa v loope
			$pad = $parentPad = $out = $xattrs = '';
			$useParent = true; // budeme obaleny vonkajsim parentom?
			$count = count($array);

			// podla hlbky (ak vobec) nastavime whitespace indent/padding
			if ($depth > 0) {
				$parentPad = str_repeat($indent, $depth - 1);
				$pad = str_repeat($indent, $depth++);
			}

			foreach ($array as $key => $value) {
				// _tag1 a _tag2 su otvaraci a zatvaraci tag

				if (is_numeric($key)) {
					// non-assoc
					$_pad = $parentPad;
					$_tag1 = $_tag2 = $parent;
					$_depth = $depth - 1;
					$useParent = false;
					$attrs = $xattrs;
				} else {
					// assoc
					$_pad = $pad;
					$_tag1 = $_tag2 = $key;
					$_depth = $depth;

					// @attributes special case
					if ($key == '@attributes') {
						foreach ((array) $value as $ak => $av) {
							// force array
							$attrs .= sprintf(
								' %s="%s"',
								htmlspecialchars($ak),
								htmlspecialchars($av),
							);
						}

						// root atributes special case
						if ($first) {
							$rootAttrs = $attrs;
							$attrs = '';
							continue;
						}

						// ak existuju aj "neatributy" tak ulozime veci tu koncime
						if ($count > 1) {
							$pAttrs = $xattrs = $attrs;
							$attrs = '';
							continue;
						}

						// inak sa upravime
						$value = '';
						$_pad = $parentPad;
						$_tag1 = $_tag2 = $parent;
						$useParent = false;
					}
				}

				//
				$parentName = $_tag2;
				$_tag1 .= $attrs;

				//
				if (is_array($value)) {
					$out .= empty($value)
						? "$_pad<$_tag1/>$n"
						: $array2xml($value, $_depth, $parentName, $attrs, $pAttrs);
				} elseif ('' == "$value") {
					$out .= "$_pad<$_tag1/>$n";
				} else {
					$out .= "$_pad<$_tag1>" . htmlspecialchars($value) . "</$_tag2>$n";
				}
			}

			if ($useParent && $parent) {
				$out = "{$parentPad}<{$parent}{$pAttrs}>{$n}{$out}{$parentPad}</{$parent}>{$n}";
			}

			$first = false;
			return $out;
		};

		$out .= $array2xml($array, $depth, null);

		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" .
			"<$rootName{$rootAttrs}>{$n}$out</$rootName>{$n}";
	}

	/**
	 * Nice and easy...
	 * @param $xmlString
	 * @return mixed
	 */
	public static function xml2array($xmlString) {
		$xml = simplexml_load_string($xmlString);
		$json = json_encode($xml);
		return json_decode($json, true);
	}
}
